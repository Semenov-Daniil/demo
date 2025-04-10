<?php

namespace common\services;

use common\models\EncryptedPasswords;
use common\models\Files;
use common\models\StudentForm;
use common\models\Students;
use common\models\Users;
use common\traits\RandomStringTrait;
use Exception;
use Yii;

class StudentService
{
    use RandomStringTrait;

    private $userService;
    private $moduleService;

    public function __construct()
    {
        $this->userService = new UserService();
        $this->moduleService = new ModuleService();
    }

    public function getTitleDb(string $login, int $numberModule): string
    {
        return "{$login}_m{$numberModule}";
    }

    public static function getTitleDirectoryModule(string $prefix, int $numberModule): string
    {
        return "{$prefix}-m{$numberModule}";
    }

    //TODO вынести в moduleService
    public function getDirectoryModuleFileTitle(int|string $moduleNumber): string
    {
        return "module-{$moduleNumber}";
    }

    public function getFolders(int $studentId, string $dirTitle): array
    {
        $folders = [];

        if (!$studentId || !($student = Students::findOne(['students_id' => $studentId]))) {
            return $folders;
        }

        if ($dirTitle == 'all') {
            foreach ($student->modules as $module) {
                $folders[] = Yii::getAlias("@students/{$student->user->login}/" . self::getTitleDirectoryModule($student->dir_prefix, $module->number));
            }
        } else {
            $folders[] = Yii::getAlias("@students/{$student->user->login}/{$dirTitle}");
        }

        return $folders;
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

            if (!($student->save() && $this->setupStudentEnvironment($student, $user->login, $user->temp_password))) {
                throw new Exception('Failed to create student');
            }

            $transaction->commit();
            return true;
        } catch (Exception $e) {
            $transaction->rollBack();
            $this->cleanupFailedStudent($user->id ?? null, $user->login ?? '');
            Yii::error("Error create student: " . $e->getMessage(), __METHOD__);
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

        $user = Users::findOne($id);
        if ($user) {
            $user->surname = $form->surname;
            $user->name = $form->name;
            $user->patronymic = $form->patronymic;
            return $user->save();
        }
        return false;
    }

    public function deleteStudentsByEvent(int $eventId): bool
    {
        $studentIds = Students::find()->select('students_id')->where(['events_id' => $eventId])->asArray()->all();
        return $this->deleteStudents($studentIds);
    }

    /**
     * Deletes multiple students.
     * @param array $studentIds
     * @return bool
     */
    public function deleteStudents(array $studentIds): bool
    {
        foreach ($studentIds as $id) {
            if (!$this->deleteStudent($id)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Deletes a single student.
     * @param int $id
     * @return bool
     */
    public function deleteStudent(int $id): bool
    {
        if (!$id || !($student = Students::findOne(['students_id' => $id]))) {
            return false;
        }

        $login = $student->user->login;
        $transaction = Yii::$app->db->beginTransaction();
        try {
            Yii::$app->fileComponent->removeDirectory(Yii::getAlias("@students/$login"));

            foreach ($student->modules as $module) {
                Yii::$app->dbComponent->deleteDb($this->getTitleDb($login, $module->number));
            }

            Yii::$app->dbComponent->deleteUser($login);

            if ($this->userService->deleteUser($id)) {
                $transaction->commit();
                return true;
            }

            $transaction->rollBack();
        } catch (Exception $e) {
            $transaction->rollBack();
        }

        return false;
    }

    /**
     * Sets up MySQL account, databases, and directories for a student.
     * @param Students $student
     * @param string $login
     * @param string $password
     * @return bool
     */
    private function setupStudentEnvironment(Students $student, string $login, string $password): bool
    {
        if (!Yii::$app->dbComponent->createUser($login, $password) ||
            !$this->createStudentDirectory($login))
        {
            return false;
        }

        foreach ($student->modules as $module) {
            if (!$this->moduleService->createStudentModuleEnvironment($student, $module)) {
                return false;
            }
        }

        $event = $student->event;
        if (!empty($event->files)) {
            $eventPath = Yii::getAlias("@events/{$event->dir_title}");
            $studentPath = Yii::getAlias("@students/$login/public");
            foreach ($event->files as $file) {
                $fileCopyDirectory = $file->modules_id ? ($this->getDirectoryModuleFileTitle($file->module->number) . '/') : '';
                if (!copy("$eventPath/{$fileCopyDirectory}{$file->save_name}.{$file->extension}", "$studentPath/{$fileCopyDirectory}{$file->save_name}.{$file->extension}")) {
                    return false;
                }
            }
        }

        return true;
    }

    public function createStudentDirectory(string $login): bool
    {
        return Yii::$app->fileComponent->createDirectory(Yii::getAlias("@students/$login")) &&
                Yii::$app->fileComponent->createDirectory(Yii::getAlias("@students/$login/public"));
    }

    /**
     * Cleans up failed student creation.
     * @param ?int $userId
     * @param string $login
     */
    private function cleanupFailedStudent(?int $userId, string $login): void
    {
        if ($login) {
            Yii::$app->fileComponent->removeDirectory(Yii::getAlias("@students/$login"));
            $student = Students::findOne(['students_id' => $userId]);
            if ($student) {
                foreach ($student->modules as $module) {
                    Yii::$app->dbComponent->deleteDb($this->getTitleDb($login, $module->number));
                }
                Yii::$app->dbComponent->deleteUser($login);
            }
        }
        if ($userId) {
            $this->userService->deleteUser($userId);
        }
    }
}