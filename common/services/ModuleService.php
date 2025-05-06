<?php

namespace common\services;

use common\models\Events;
use common\models\Modules;
use common\models\Students;
use Exception;
use Yii;
use yii\helpers\VarDumper;

class ModuleService
{
    public string $logFile = '';

    private VirtualHostService $vhostService;
    private array $filesModule = [
        'access.log' => '',
        'error.log' => '',
        'index.php' => '<?php echo "Welcome, Student to Module";'
    ];

    public function __construct()
    {
        $this->vhostService = new VirtualHostService();
        $this->logFile ='modules.log';
    }

    public static function getDirectoryModuleFileTitle(int $moduleNumber): string
    {
        return "module-{$moduleNumber}";
    }

    public static function getTitleDirectoryModule(string $prefix, int $moduleNumber): string
    {
        return "{$prefix}-m{$moduleNumber}";
    }

    public function getTitleDb(string $login, int $moduleNumber): string
    {
        return "{$login}_m{$moduleNumber}";
    }

    public function createModule(Modules $model)
    {
        if (!$model->validate()) {
            return false;
        }

        $transaction = Yii::$app->db->beginTransaction();
        try {
            if (!$model->save()) {
                throw new Exception('Failed to save module');
            }

            if (!$this->createEventModuleDirectory($model->event->dir_title, $model->number)) {
                throw new Exception('Failed to create event module directory');
            }

            $students = Students::findAll(['events_id' => $model->events_id]);
            foreach ($students as $student) {
                if (!$this->createStudentModuleEnvironment($student, $model)) {
                    throw new Exception("Failed to create environment for student {$student->user->login}");
                }
            }

            $transaction->commit();
            return true;
        } catch (Exception $e) {
            $transaction->rollBack();
            $this->deleteModule($model->id);
            Yii::error("Error creating module: " . $e->getMessage(), __METHOD__);
            return false;
        }
    }

    public function createEventModuleDirectory(string $eventDirTitle, int $moduleNumber): bool
    {
        return Yii::$app->fileComponent->createDirectory(Yii::getAlias("@events/{$eventDirTitle}/" . $this->getDirectoryModuleFileTitle($moduleNumber)));
    }

    public function createStudentModuleEnvironment(Students $student, Modules $module): bool
    {
        $login = $student->user->login;
        $dbName = $this->getTitleDb($login, $module->number);
        $studentModuleDir = $this->getTitleDirectoryModule($student->dir_prefix, $module->number);
        $publicModuleDir = $this->getDirectoryModuleFileTitle($module->number);

        if (!Yii::$app->dbComponent->createDb($dbName) ||
            !Yii::$app->dbComponent->changePrivileges($login, $dbName, $module->status)
        ) {
            throw new Exception("Failed to create and configure the module database: {$dbName}");
        }

        if (!Yii::$app->fileComponent->createDirectory(Yii::getAlias("@students/{$login}/{$studentModuleDir}")) ||
            !Yii::$app->fileComponent->createDirectory(Yii::getAlias("@students/{$login}/public/{$publicModuleDir}"))
        ) {
            throw new Exception("Failed to create module folders: {$login}/{$studentModuleDir}");
        }

        if (!$this->addFilesToModule(Yii::getAlias("@students/{$login}/{$studentModuleDir}"))) {
            throw new Exception("Failed to create module files: {$login}/{$studentModuleDir}");
        }

        $this->vhostService->createVirtualHost(Yii::getAlias("@students/{$login}/{$studentModuleDir}"));

        $this->setupModuleDir($login, Yii::getAlias("@students/{$login}/{$studentModuleDir}"));

        return true;
    }

    private function addFilesToModule(string $path): bool
    {
        foreach ($this->filesModule as $filename => $content) {
            file_put_contents("$path/$filename", $content);
        }

        return true;
    } 

    private function setupModuleDir(string $login, string $path)
    {
        $output = Yii::$app->commandComponent->executeBashScript(Yii::getAlias('@bash/system/setup_module_dirs.sh'), [$login, $path, "--log={$this->logFile}"]);
        
        if (!$output['returnCode']) {
            throw new Exception("Failed to setup module directory '{$path}': {$output['stderr']}");
        }
        
        return true;
    }

    /**
     * Changes the activity status of the module.
     * 
     * @return bool returns the value `true` if the status has been successfully changed.
     * 
     * @throws Exception|Throwable generated an exception if an error occurred when changing the status.
     */
    public function changeStatus(Modules $module, int $status): bool
    {
        $transaction = Yii::$app->db->beginTransaction();
        try {
            $module->status = $status;
            if (!$module->save()) {
                throw new Exception('Failed to update module status');
            }

            if (!$this->changePrivilegesDbStudents($module)) {
                throw new Exception('Failed to update student privileges');
            }

            $transaction->commit();
            return true;
        } catch (Exception $e) {
            $transaction->rollBack();
            Yii::error("Error changing status: " . $e->getMessage(), __METHOD__);
            return false;
        }
    }

    public function changePrivilegesDbStudents(Modules $module): bool
    {
        $students = Students::findAll(['events_id' => $module->events_id]);

        foreach ($students as $student) {
            $login = $student->user->login;
            $dbName = $this->getTitleDb($login, $module->number);
            
            if (!Yii::$app->dbComponent->changePrivileges($login, $dbName, $module->status)) {
                return false;
            }
        }
        return true;
    }

    public function deleteModules(array $moduleIds): bool
    {
        foreach ($moduleIds as $id) {
            if (!$this->deleteModule($id)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Deletes the module.
     * 
     * @param string $id module ID.
     * 
     * @return bool return `true` if the module was successfully deleted.
     */
    public function deleteModule(?int $id): bool
    {
        if (!$id) {
            return false;
        }

        $transaction = Yii::$app->db->beginTransaction();
        try {
            $module = Modules::findOne(['id' => $id]);
            if (!$module) {
                return false;
            }

            $this->deleteModulesStudents($module);

            $event = $module->event;
            $moduleDirTitle = $this->getDirectoryModuleFileTitle($module->number);
            Yii::$app->fileComponent->removeDirectory(Yii::getAlias("@events/{$event->dir_title}/{$moduleDirTitle}"));

            if (!$module->delete()) {
                throw new Exception('Failed to delete module');
            }

            $transaction->commit();
            return true;
        } catch (Exception $e) {
            $transaction->rollBack();
            Yii::error("Error delete module: " . $e->getMessage(), __METHOD__);
            return false;
        }
    }

    /**
     * Deletes a module from students.
     * 
     * @return bool return `true` if the module was successfully deleted.
     */
    public function deleteModulesStudents(Modules $module): bool
    {
        $students = Students::find()
            ->where(['events_id' => $module->events_id])
            ->joinWith('user')
            ->all()
        ;
        
        foreach ($students as $student) {
            $this->deleteModuleStudent($student, $module);
        }

        return true;
    }

    /**
     * Deletes a module from student.
     * 
     * @return bool return `true` if the module was successfully deleted.
     */
    public function deleteModuleStudent(Students $student, Modules $module): bool
    {
        try {
            $login = $student->user->login;
            $dbName = $this->getTitleDb($login, $module->number);
            $studentModuleDir = $this->getTitleDirectoryModule($student->dir_prefix, $module->number);
    
            $this->vhostService->disableVirtualHost(Yii::getAlias("@students/{$login}/{$studentModuleDir}"));
    
            Yii::$app->dbComponent->deleteDb($dbName);
    
            Yii::$app->fileComponent->removeDirectory(Yii::getAlias("@students/{$login}/{$studentModuleDir}"));
            
            return true;
        } catch (\Exception $e) {
            throw new Exception("Failed to delete module {$studentModuleDir} student {$login}: {$e}");
            return false;
        }
    }

    public function clearModules(array $moduleIds): bool
    {
        foreach ($moduleIds as $id) {
            if (!$this->clearModule($id)) {
                return false;
            }
        }
        return true;
    }

    public function clearModule(string $id): bool
    {
        $transaction = Yii::$app->db->beginTransaction();   
        try {
            $module = Modules::findOne($id);
            if (!$module) {
                return false;
            }

            if (!$this->clearModulesStudents($module)) {
                throw new Exception('Failed to clear student resources');
            }

            $transaction->commit();
            return true;
        } catch (Exception $e) {
            $transaction->rollBack();
            Yii::error("Error clearing module: " . $e->getMessage(), __METHOD__);
            return false;
        }
    }

    public function clearModulesStudents(Modules $module): bool
    {
        $students = Students::find()
            ->where(['events_id' => $module->events_id])
            ->joinWith('user')
            ->all()
        ;
        
        foreach ($students as $student) {
            $login = $student->user->login;
            $dbName = $this->getTitleDb($login, $module->number);
            $studentModuleDir = $this->getTitleDirectoryModule($student->dir_prefix, $module->number);

            if (!Yii::$app->dbComponent->clearDatabaseByName($dbName) ||
                !Yii::$app->fileComponent->clearDirectory(Yii::getAlias("@students/{$login}/{$studentModuleDir}"), false))
            {
                return false;
            }
        }

        return true;
    }
}