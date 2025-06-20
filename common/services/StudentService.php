<?php

namespace common\services;

use common\jobs\students\DeleteStudentEnvironment;
use common\jobs\students\SetupStudentEvironment;
use common\models\EncryptedPasswords;
use common\models\Files;
use common\models\Statuses;
use common\models\StudentForm;
use common\models\Students;
use common\models\Users;
use common\traits\RandomStringTrait;
use Exception;
use Yii;

class StudentService
{
    use RandomStringTrait;

    public string $files_dir = '';
    public string $logFile = '';

    private $userService;
    private $moduleService;

    public function __construct()
    {
        $this->logFile = 'students.log';
        $this->files_dir = (new FileService())::FILES_DIR;
        $this->userService = new UserService();
        $this->moduleService = new ModuleService();
    }

    public function getEventChannel($id)
    {
        return Yii::$app->sse::STUDENT_CHANNEL . "_event_$id";
    }

    public function getTitleDb(string $login, int $numberModule): string
    {
        return "{$login}_m{$numberModule}";
    }

    public function getDirectories(int $studentId, int|null $module = null): array
    {
        $dirs = [];

        if (!$studentId || !($student = Students::findOne(['students_id' => $studentId]))) {
            return $dirs;
        }

        $login = $student->user->login;

        if ($module) {
            return [Yii::getAlias("@students/$login/" . $this->moduleService->getTitleDirectoryModule($student->dir_prefix, $module))];
        }

        foreach ($student->modules as $module) {
            $dirs[] = Yii::getAlias("@students/$login/" . $this->moduleService->getTitleDirectoryModule($student->dir_prefix, $module->number));
        }

        return $dirs;
    }

    public function getDatabases(int $id, int|null $module = null): array
    {
        $databeses = [];

        if (!$id || !($student = Students::findOne(['students_id' => $id]))) {
            return $databeses;
        }

        $login = $student->user->login;

        if ($module) {
            return [$this->moduleService->getTitleDb($login, $module)];
        }

        foreach ($student->modules as $module) {
            $databeses[] = $this->moduleService->getTitleDb($login, $module->number);
        }

        return $databeses;
    }

    public function getFilesDirectory(string $login): string
    {
        return Yii::getAlias("@students/$login/{$this->files_dir}");
    }

    public function createStudent(StudentForm $form): bool
    {
        if (!$form->validate()) {
            return false;
        }

        $transaction = Yii::$app->db->beginTransaction();
        try {
            $user = $this->userService->createBaseUser(Students::TITLE_ROLE_STUDENT, [
                'surname' => $form->surname,
                'name' => $form->name,
                'patronymic' => $form->patronymic,
            ]);

            $student = new Students();
            $student->students_id = $user->id;
            $student->events_id = $form->events_id;
            $student->dir_prefix = $this->generateRandomString(8, ['lowercase']);

            if (!$student->save()) throw new Exception('Failed to create student');

            Yii::$app->queue->push(new SetupStudentEvironment([
                'student' => $student,
                'login' => $user->login,
                'password' => $user->temp_password
            ]));

            $transaction->commit();
            return true;
        } catch (Exception $e) {
            $transaction->rollBack();
            Yii::error("Error create student: " . $e->getMessage(), __METHOD__);

            if ($student->students_id) {
                $this->deleteStudent($student->students_id);
            } else if ($user->id) {
                $this->userService->deleteUser($user->id);
            }

            return false;
        }
    }

    /**
     * Updates an existing student.
     * @param int $id
     * @param StudentForm $form
     * @return bool
     */
    public function updateStudent(int $id, StudentForm $form): bool
    {
        if (!$form->validate()) {
            return false;
        }

        return $this->userService->updateUser($id, [
            'surname' => $form->surname, 
            'name' => $form->name, 
            'patronymic' => $form->patronymic,
            'updated_at' => $form->updated_at,
        ]);
    }

    public function deleteStudentsByEvent(int $eventId): bool
    {
        $studentIds = Students::find()->select('students_id')->where(['events_id' => $eventId])->column();
        return $this->deleteStudents($studentIds);
    }

    /**
     * Deletes multiple students.
     * @param array $studentIds
     * @return bool
     */
    public function deleteStudents(array $studentIds): bool
    {
        Users::updateAll(['statuses_id' => Statuses::getStatusId(Statuses::DELETING)], ['id' => $studentIds]);
        Yii::$app->sse->publish($this->getEventChannel(Students::findOne(['students_id' => $studentIds[0]])?->events_id), 'student-delete');
        foreach ($studentIds as $id) {
            Yii::$app->queue->push(new DeleteStudentEnvironment(['studentId' => $id]));
        }
        return true;
    }

    /**
     * Deletes a single student.
     * @param int $id
     * @return bool
     */
    public function deleteStudent(int|null $id = null): bool
    {
        if (!$id || !($student = Students::findOne(['students_id' => $id]))) {
            Yii::warning("Failed to find user ($id)", __METHOD__);
            return true;
        }

        $login = $student->user->login;
        $transaction = Yii::$app->db->beginTransaction();
        try {
            foreach ($student->modules as $module) {
                $this->moduleService->deleteModuleStudent($student, $module);
            }
            
            $studentDir = Yii::getAlias("@students/$login/{$this->files_dir}");
            $this->removeFilesEvent($studentDir);
            
            $this->deleteSystemUser($login);

            Yii::$app->fileComponent->removeDirectory(Yii::getAlias("@students/$login"));

            Yii::$app->dbComponent->deleteUser($login);

            if (!$this->userService->deleteUser($id)) throw new \Exception("Failed to delete user ($id)");
            
            $transaction->commit();
            return true;
        } catch (Exception $e) {
            $transaction->rollBack();
            Yii::error("\nError delete student:\n{$e->getMessage()}", __METHOD__);
            return false;
        }
    }

    /**
     * Sets up MySQL account, databases, and directories for a student.
     * @param Students $student
     * @param string $login
     * @param string $password
     * @return bool
     */
    public function setupStudentEnvironment(Students $student, string $login, string $password): bool
    {
        if (!Yii::$app->dbComponent->createUser($login, $password)) {
            throw new Exception('Failed to create mysql account.');
        }

        if (!$this->createStudentDirectory($login)) {
            throw new Exception('Failed to create directory.');
        }

        $this->createSystemUser($login, $password);

        foreach ($student->modules as $module) {
            $this->moduleService->createStudentModuleEnvironment($student, $module);
        }

        $eventDir = Yii::getAlias("@events/{$student->event->dir_title}");
        $studentDir = Yii::getAlias("@students/$login/{$this->files_dir}");
        $this->setupFilesEvent($eventDir, $studentDir);

        $student->user->statuses_id = Statuses::getStatusId(Statuses::READY);
        if ($student->user->update() === false) throw "Failed to update the student's status to ready";
        
        return true;
    }

    public function createStudentDirectory(string $login): bool
    {
        return Yii::$app->fileComponent->createDirectory(Yii::getAlias("@students/$login")) &&
                Yii::$app->fileComponent->createDirectory($this->getFilesDirectory($login));
    }

    private function createSystemUser(string $login, string $password): bool
    {
        $studentDir = Yii::getAlias("@students/{$login}");
        $output = Yii::$app->commandComponent->executeBashScript(Yii::getAlias('@bash/system/create_user.sh'), [$login, $password, $studentDir, "--log={$this->logFile}"]);
        if ($output['returnCode']) {
            throw new Exception("Failed to create user {$login}: {$output['stderr']}");
        }
        return true;
    }

    private function setupFilesEvent(string $event_dir, string $student_dir): bool
    {
        $output = Yii::$app->commandComponent->executeBashScript(Yii::getAlias('@bash/utils/mount.sh'), [$event_dir, $student_dir, "ro", "--log={$this->logFile}"]);
        if ($output['returnCode']) {
            throw new Exception("\nFailed to set up student file directory '$student_dir':\n{$output['stderr']}");
        }
        return true;
    }

    private function removeFilesEvent(string $student_dir): bool
    {
        $output = Yii::$app->commandComponent->executeBashScript(Yii::getAlias('@bash/utils/umount.sh'), [$student_dir, "--log={$this->logFile}"]);
        if ($output['returnCode']) {
            throw new Exception("\nFailed to remove student file directory '$student_dir':\n{$output['stderr']}");
        }
        return true;
    }

    private function deleteSystemUser(string $login): bool
    {
        $output = Yii::$app->commandComponent->executeBashScript(Yii::getAlias('@bash/system/delete_user.sh'), [$login, "--log={$this->logFile}"]);
        if ($output['returnCode']) {
            throw new Exception("Failed to delete user {$login}: {$output['stderr']}\n{$output['stdout']}");
        }
        return true;
    }
}