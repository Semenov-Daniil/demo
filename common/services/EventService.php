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

    private $studentService;

    public function __construct()
    {
        $this->studentService = new StudentService();
    }

    public function getDirectoryModuleFileTitle(int|string $moduleNumber): string
    {
        return "module-{$moduleNumber}";
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
            $event->experts_id = $eventModel->expert ?: Yii::$app->user->id;
            $event->dir_title = $this->generateUniqueDirectoryTitle(8, ['lowercase']);

            if ($event->save()
                    && Yii::$app->fileComponent->createDirectory(Yii::getAlias("@events/{$event->dir_title}"))
                    && $this->createModulesForEvent($event, $eventModel->countModules)) {
                $transaction->commit();
                return true;
            }
            
            $transaction->rollBack();
        } catch (Exception $e) {
            $transaction->rollBack();
            var_dump($e);die;
        }

        $this->deleteEvent($event?->id);
        return false;
    }

    // TODO изменить с использованием методов из modulService
    public function createModulesForEvent(Events $event, int $countModules): bool
    {
        for ($i = 0; $i < $countModules; $i++) {
            $module = new Modules(['events_id' => $event->id]);
            if (!($module->save() && $this->createModuleDirectory($event->dir_title, $module->number))) {
                return false;
            }
        }

        return true;
    }

    // TODO вынести метод в modelService
    public function createModuleDirectory(string $eventDirTitle, int $moduleNumber): bool
    {
        $dirPath = Yii::getAlias("@events/{$eventDirTitle}/" . $this->getDirectoryModuleFileTitle($moduleNumber));
        return Yii::$app->fileComponent->createDirectory($dirPath);
    }

    private function deleteEventDirectory(string $dirTitle): void
    {
        Yii::$app->fileComponent->removeDirectory(Yii::getAlias("@events/{$dirTitle}"));
    }

    /**
     * Deletes a single event with transaction.
     * @param int|string $id
     * @return bool
     * @throws Exception
     */
    public function deleteEvent(int|string|null $id): bool
    {
        if (!$id || !($event = Events::findOne($id))) {
            return false;
        }

        $transaction = Yii::$app->db->beginTransaction();

        try {
            if ($this->studentService->deleteStudentsByEvent($event->id)
                && $event->delete()
            ) {
                $this->deleteEventDirectory($event->dir_title);
                $transaction->commit();
                return true;
            }
        } catch (\Exception $e) {
            Yii::error("Ошибка удаления события $id: " . $e->getMessage(), __METHOD__);
        }

        $transaction->rollBack();
        return false;
    }

    /**
     * Deletes multiple events.
     * @param array $eventIds
     * @return bool
     */
    public function deleteEvents(array $eventIds): bool
    {
        foreach ($eventIds as $id) {
            if (!$this->deleteEvent($id)) {
                return false;
            }
        }
        return true;
    }

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
    
        while(Events::find()->where(['dir_title' => $attr])->exists()) {
            $attr = $this->generateRandomString($length, $charSets);
        }

        return $attr;
    }
}