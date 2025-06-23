<?php

namespace common\services;

use common\jobs\modules\ChangePrivilegesStudents;
use common\jobs\modules\DeleteModuleEvironment;
use common\jobs\modules\SetupModuleEvironment;
use common\models\Events;
use common\models\Modules;
use common\models\Statuses;
use common\models\Students;
use Exception;
use Yii;
use yii\helpers\VarDumper;

use function PHPUnit\Framework\returnSelf;

class ModuleService
{
    public string $logFile = '';
    public string $tempIndex = 'temp_index.html';

    private VirtualHostService $vhostService;
    private array $filesModule = [
        'access.log' => '',
        'error.log' => '',
    ];

    public function __construct()
    {
        $this->vhostService = new VirtualHostService();
        $this->logFile ='modules.log';
    }

    public function getEventChannel($id)
    {
        return Yii::$app->sse::MODULE_CHANNEL . "_event_$id";
    }

    public static function getDirectoryModuleFileTitle(int $moduleNumber, bool $show = true): string
    {
        return ($show ? '' : '.') . "module-{$moduleNumber}";
    }

    public static function getTitleDirectoryModule(string $prefix, int $moduleNumber, bool $show = true): string
    {
        return ($show ? '' : '.') . "{$prefix}-m{$moduleNumber}";
    }

    public function getTitleDb(string $login, int $moduleNumber): string
    {
        return "{$login}_m{$moduleNumber}";
    }

    public function createModule(Modules $model)
    {
        if (!$model->validate()) {
            Yii::error("Error creating module: incorrect module validation", __METHOD__);
            return false;
        }

        $transaction = Yii::$app->db->beginTransaction();
        try {
            if (!$model->save()) throw new Exception('Failed to save the module record to the database');

            Yii::$app->queue->push(new SetupModuleEvironment([
                'module' => $model,
            ]));

            $transaction->commit();
            return true;
        } catch (Exception $e) {
            $transaction->rollBack();
            $this->deleteModule($model->id);
            Yii::error("Error creating module: " . $e->getMessage(), __METHOD__);
            return false;
        }
    }

    public function setupModuleEnvironment(Modules $model): bool
    {
        $moduleEventPath = Yii::getAlias("@events/{$model->event->dir_title}/" . $this->getDirectoryModuleFileTitle($model->number, $model->status));
        Yii::$app->fileComponent->createDirectory($moduleEventPath);

        $students = Students::findAll(['events_id' => $model->events_id]);
        foreach ($students as $student) {
            if (!$this->createStudentModuleEnvironment($student, $model)) {
                throw new Exception("Failed to create environment for student {$student->user->login}");
            }
        }

        $this->updatePermissionModuleEvent($moduleEventPath, $model->status);

        $model->statuses_id = Statuses::getStatusId(Statuses::READY);
        if ($model->update() === false) throw "Failed to update the modules's status to ready";

        return true;
    }

    private function updatePermissionModuleEvent(string $path, bool $status): bool
    {
        return Yii::$app->fileComponent->updatePermission($path, $status ? "775" : "770", Yii::$app->params['siteUser'] . ':' . Yii::$app->params['siteGroup'], "--log={$this->logFile}");
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

        if (!Yii::$app->dbComponent->createDb($dbName) ||
            !Yii::$app->dbComponent->changePrivileges($login, $dbName, $module->status)
        ) {
            throw new Exception("Failed to create and configure the module database: {$dbName}");
        }

        Yii::$app->fileComponent->createDirectory(Yii::getAlias("@students/{$login}/{$studentModuleDir}"));

        if (!$this->addFilesToModule($module, $student)) {
            throw new Exception("Failed to create module files: {$login}/{$studentModuleDir}");
        }

        $this->vhostService->createVirtualHost($login, $studentModuleDir, Yii::getAlias("@students/{$login}/{$studentModuleDir}"));

        $this->changePrivilegesStudent($module, $student);

        return true;
    }

    private function updatePermissionModule(Modules $module, string $moduleDirectory, string $login): bool
    {
        return Yii::$app->fileComponent->updatePermission($moduleDirectory, $module->status ? "770" : "070", "$login:" . Yii::$app->params['siteGroup'], "--log={$this->logFile}");
    }

    private function addFilesToModule(Modules $module, Students $student): bool
    {
        $login = $student->user->login;
        $studentModuleDir = $this->getTitleDirectoryModule($student->dir_prefix, $module->number, $module->status);
        $studentDir = Yii::getAlias("@students/$login/$studentModuleDir");
        foreach ($this->filesModule as $filename => $content) {
            file_put_contents("$studentDir/$filename", $content);
        }

        $index = "$studentDir/index.html";
        $tempIndex = Yii::getAlias("@templates/{$this->tempIndex}");
        $content = file_get_contents($tempIndex);
        $content = str_replace('${number}', $module->number, $content);
        file_put_contents($index, $content);
        Yii::$app->fileComponent->updatePermission($index, "775", "$login:" . Yii::$app->params['siteGroup'], "--log={$this->logFile}");

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

            Yii::$app->queue->push(new ChangePrivilegesStudents([
                'module' => $module,
            ]));

            $transaction->commit();
            return true;
        } catch (Exception $e) {
            $transaction->rollBack();
            Yii::error("Error changing status: " . $e->getMessage(), __METHOD__);
            return false;
        }
    }

    public function changePrivilegesStudents(Modules $module): bool
    {
        $students = Students::findAll(['events_id' => $module->events_id]);

        foreach ($students as $student) {
            if (!$this->changePrivilegesStudent($module, $student)) return false;
        }


        $moduleOldDir = Yii::getAlias("@events/{$module->event->dir_title}/" . $this->getDirectoryModuleFileTitle($module->number, !$module->status));
        $moduleNewDir = Yii::getAlias("@events/{$module->event->dir_title}/" . $this->getDirectoryModuleFileTitle($module->number, $module->status));
        if (file_exists($moduleOldDir)) {
            if (!rename($moduleOldDir, $moduleNewDir)) {
                return false;
            }
        }

        $this->updatePermissionModuleEvent($moduleNewDir, $module->status);

        return true;
    }

    private function changePrivilegesStudent(Modules $module, Students $student): bool
    {
        $login = $student->user->login;

        $dbName = $this->getTitleDb($login, $module->number);
        if (!Yii::$app->dbComponent->changePrivileges($login, $dbName, $module->status)) {
            return false;
        }

        $moduleOldDir = Yii::getAlias("@students/{$login}/" . $this->getTitleDirectoryModule($student->dir_prefix, $module->number, !$module->status));
        $moduleNewDir = Yii::getAlias("@students/{$login}/" . $this->getTitleDirectoryModule($student->dir_prefix, $module->number, $module->status));
        if (file_exists($moduleOldDir)) {
            if (!rename($moduleOldDir, $moduleNewDir)) {
                return false;
            }
        }

        $this->updatePermissionModule($module, $moduleNewDir, $login);

        $moduleName = $this->getTitleDirectoryModule($student->dir_prefix, $module->number);
        $this->vhostService->changeStatusVirtualHost($moduleName, $module->status);

        return true;
    }

    public function deleteModules(array $moduleIds): bool
    {
        Modules::updateAll(['statuses_id' => Statuses::getStatusId(Statuses::DELETING)], ['id' => $moduleIds]);

        if ($moduleIds && $module = Modules::findOne(['id' => $moduleIds[0]])) {
            Yii::$app->sse->publish($this->getEventChannel($module->events_id), 'delete-module');
            (new EventService())->publishEvent($module->event->experts_id, 'delete-module');
        }

        foreach ($moduleIds as $id) {
            Yii::$app->queue->push(new DeleteModuleEvironment(['moduleId' => $id]));
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
            $moduleDirTitle = $this->getDirectoryModuleFileTitle($module->number, $module->status);
            Yii::$app->fileComponent->removeDirectory(Yii::getAlias("@events/{$event->dir_title}/{$moduleDirTitle}"));

            if (!$module->delete()) throw new Exception('Failed to delete module');

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
        $studentModuleDir = '';
        try {
            $login = $student->user->login;
            $dbName = $this->getTitleDb($login, $module->number);
            $studentModuleDir = $this->getTitleDirectoryModule($student->dir_prefix, $module->number);
    
            $this->vhostService->deleteVirtualHost($studentModuleDir);
    
            Yii::$app->dbComponent->deleteDb($dbName);
    
            $currentModuleDir = $this->getTitleDirectoryModule($student->dir_prefix, $module->number, $module->status);
            Yii::$app->fileComponent->removeDirectory(Yii::getAlias("@students/{$login}/{$currentModuleDir}"));
            
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
            $studentModuleDir = $this->getTitleDirectoryModule($student->dir_prefix, $module->number, $module->status);

            if (!Yii::$app->dbComponent->clearDatabaseByName($dbName) ||
                !Yii::$app->fileComponent->clearDirectory(Yii::getAlias("@students/{$login}/{$studentModuleDir}"), false))
            {
                return false;
            }
        }

        return true;
    }
}