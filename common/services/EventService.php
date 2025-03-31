<?php

namespace common\services;

use common\models\EventForm;
use common\models\Events;
use common\models\ExpertForm;
use common\models\Experts;
use common\models\Modules;
use common\models\Students;
use common\models\Users;
use common\traits\RandomStringTrait;
use Exception;
use Yii;
use yii\helpers\VarDumper;

class EventService
{
    use RandomStringTrait;

    public function createEventDirectory(string $dirTitle): bool
    {
        return Yii::$app->fileComponent->createDirectory(Yii::getAlias("@events/$dirTitle"));
    }

    public function createModulesForEvent(Events $event, int $countModules): bool
    {
        for ($i = 0; $i < $countModules; $i++) {
            $module = new Modules(['events_id' => $event]);
            if (!($module->save() && $this->createModuleDirectory($event->dir_title, $module->number))) {
                return false;
            }
        }

        return true;
    }

    public function createModuleDirectory(string $eventDirTitle, int $moduleNumber): bool
    {
        $dirPath = Yii::getAlias("@events/{$eventDirTitle}/" . $this->getDirectoryModuleFileTitle($moduleNumber));
        return Yii::$app->fileComponent->createDirectory($dirPath);
    }

    public function getDirectoryModuleFileTitle(int|string $moduleNumber): string
    {
        return "module-{$moduleNumber}"
    }

    /**
     * Creates a new event from Events model data.
     * @param Experts $eventModel
     * @return bool
     * @throws Exception
     */
    public function createEvent(EventForm $eventModel): bool
    {
        if (!$eventModel->validate()) {
            return false;
        }

        $transaction = Yii::$app->db->beginTransaction();
        try {
            $event = new Events();
            $event->title = $eventModel->title;
            $event->countModules = $eventModel->countModules;
            $event->experts_id = $eventModel->expert ? $eventModel->expert : Yii::$app->user->id;
            $event->dir_title = $this->generateUniqueDirectoryTitle(8, ['lowercase']);

            if ($event->save()
                    && $this->createEventDirectory($event->dir_title)
                    && $this->createModulesForEvent($event, $countModules)) {
                $transaction->commit();
                return true;
            }
            
            $transaction->rollBack();
        } catch (Exception $e) {
            $transaction->rollBack();
            $event?->id ?? $event->delete();
        }

        return false;
    }

    // /**
    //  * Updates an existing expert.
    //  * @param int $id
    //  * @param Experts $expertModel
    //  * @return bool
    //  */
    // public function updateExpert(int $id, ExpertForm $expertModel): bool
    // {
    //     if (!$expertModel->validate()) {
    //         return false;
    //     }

    //     $user = Users::findOne($id);
    //     if ($user) {
    //         $user->surname = $expertModel->surname;
    //         $user->name = $expertModel->name;
    //         $user->patronymic = $expertModel->patronymic;
    //         return $user->save();
    //     }
    //     return false;
    // }

    // /**
    //  * Deletes multiple experts.
    //  * @param array $expertIds
    //  * @return bool
    //  */
    // public function deleteExperts(array $expertIds): bool
    // {
    //     foreach ($expertIds as $id) {
    //         if (!$this->deleteExpert($id)) {
    //             return false;
    //         }
    //     }
    //     return true;
    // }

    // private function deleteExpert(?int $id): bool
    // {
    //     $user = Users::findOne($id);
    //     if (!$user || $user->id === Yii::$app->user->id) {
    //         return false;
    //     }

    //     $transaction = Yii::$app->db->beginTransaction();
    //     try {
    //         $eventIds = array_column($user->events, 'id');
    //         $studentIds = Students::find()->select('students_id')->where(['events_id' => $eventIds])->asArray()->all();
            
    //         $this->studentService->deleteStudents($studentIds);
    //         Events::removeDirectory($eventIds);
    
    //         return $this->userService->deleteUser($id);
    //     } catch (Exception $e) {
    //         $transaction->rollBack();
    //         var_dump($e);die;
    //     }

    //     return false;
    // }

    /**
     * Generates a unique value for the dir_title.
     * 
     * @param int $length Length of the generated string
     * @param array $charSets Character sets for random string generation
     * 
     * @return string
     */
    public function generateUniqueDirectoryTitle(int $length = 32, array $charSets = []): string
    {
        $attr = $this->generateRandomString($length, $charSets);
    
        while(Events::find()->where(['dir_prefix' => $attr])->exists()) {
            $attr = $this->generateRandomString($length, $charSets);
        }

        return $attr;
    }
}