<?php

namespace common\services;

use common\jobs\events\SetupEventEvironment;
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

    public string $public_dir = '';
    private $studentService;
    private $moduleService;

    public function __construct()
    {
        $this->public_dir = (new FileService())::PUBLIC_DIR;
        $this->studentService = new StudentService();
        $this->moduleService = new ModuleService();
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
            Yii::error("Error create event: incorrect event validation", __METHOD__);
            return false;
        }

        $transaction = Yii::$app->db->beginTransaction();
        try {
            $event = new Events();
            $event->title = $eventModel->title;
            $event->experts_id = $eventModel->expert ?: Yii::$app->user->id;
            $event->dir_title = $this->generateUniqueDirectoryTitle(8, ['lowercase']);

            if (!$event->save()) throw new Exception('Failed to save the event record to the database');

            Yii::$app->queue->push(new SetupEventEvironment([
                'eventId' => $event->id,
                'eventDir' => $event->dir_title,
                'countModules' => $eventModel->countModules
            ]));
            
            $transaction->commit();
            return true;
        } catch (Exception $e) {
            $transaction->rollBack();
            $this->deleteEvent($event->id ?? null);
            Yii::error("Error create event: " . $e->getMessage(), __METHOD__);
            return false;
        }
    }

    public function createEventDirectories(string $event_dir): bool
    {
        return Yii::$app->fileComponent->createDirectory(Yii::getAlias("@events/{$event_dir}"))
                && Yii::$app->fileComponent->createDirectory(Yii::getAlias("@events/{$event_dir}/{$this->public_dir}"));
    }

    public function createModulesEvent(int $eventId, int $countModules): bool
    {
        for ($i = 0; $i < $countModules; $i++) {
            $module = new Modules(['events_id' => $eventId]);
            if (!$this->moduleService->createModule($module)) return false;
        }
        return true;
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
            if (!$this->studentService->deleteStudentsByEvent($event->id)) throw new Exception("Failed to delete event students");
            
            if ($event->delete()) throw new Exception("Failed to delete event record from the database");

            Yii::$app->fileComponent->removeDirectory(Yii::getAlias("@events/{$event->dir_title}"));
            
            $transaction->commit();
            return true;
        } catch (\Exception $e) {
            $transaction->rollBack();
            Yii::error("\nFailed delete event $id:\n" . $e->getMessage(), __METHOD__);
            return false;
        }
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