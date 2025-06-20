<?php

namespace common\services;

use common\jobs\events\DeleteEventEnvironment;
use common\jobs\events\SetupEventEvironment;
use common\models\EventForm;
use common\models\Events;
use common\models\ExpertForm;
use common\models\Experts;
use common\models\Modules;
use common\models\Statuses;
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

    public function getExpertChannel($id)
    {
        return Yii::$app->sse::EXPERT_CHANNEL . "_expert_$id";
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

    public function updateEvent(int $id, EventForm $eventModel): bool
    {
        if (!$eventModel->validate()) {
            Yii::error("Error update event: incorrect event validation", __METHOD__);
            return false;
        }

        $transaction = Yii::$app->db->beginTransaction();
        try {
            $event = Events::findOne(['id' => $id]);
            $event->scenario = $event::SCENARIO_UPDATE;
            $lastUpdate = $event->updated_at;

            $event->attributes = $eventModel->attributes;
            $event->experts_id = $eventModel->expertUpdate;

            if (strtotime($lastUpdate) > strtotime($event->updated_at)) throw new \yii\db\StaleObjectException("Attempt to update old data");

            if ($event->update() === false) throw new Exception("Failed to update the event");
            $transaction->commit();
            return true;
        } catch (\yii\db\StaleObjectException $e) {
            $transaction->rollBack();
            Yii::warning("Attempt to update old data event '$event->id'");
            Yii::$app->toast->addToast(
                'Устаревшие данные события. Пожалуйста, обновите форму.',
                'info'
            );
            return false;
        } catch (\Exception $e) {
            $transaction->rollBack();
            Yii::error("\nFailed to update the event '$event->id':\n{$e->getMessage()}", __METHOD__);
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
        Events::updateAll(['statuses_id' => Statuses::getStatusId(Statuses::DELETING)], ['id' => $eventIds]);
        $this->publishEvent(Events::getExpertsEvents($eventIds), 'event-delete');
        foreach ($eventIds as $id) {
            Yii::$app->queue->push(new DeleteEventEnvironment(['eventId' => $id]));
        }
        return true;
    }

    public function deleteEventEnvironment(?int $id): bool
    {
        $event = Events::findOne(['id' => $id]);
        if (!$event) return true;

        try {
            if (!$this->studentService->deleteStudentsByEvent($event->id)) throw new Exception("Failed to delete event students");
            return true;
        } catch (Exception $e) {
            Yii::error("\nFailed delete event ($id):\n{$e->getMessage()}", __METHOD__);
            return false;
        }
    }

    /**
     * Deletes a single event with transaction.
     * @param int|string $id
     * @return bool
     * @throws Exception
     */
    public function deleteEvent(int|string|null $id): bool
    {
        if (!$id || !($event = Events::findOne(['id' => $id]))) {
            Yii::warning("Failed to find event ($id)", __METHOD__);
            return true;
        }

        $transaction = Yii::$app->db->beginTransaction();
        try {
            Yii::$app->fileComponent->removeDirectory(Yii::getAlias("@events/{$event->dir_title}"));
            if (!$event->delete()) throw new Exception("Failed to delete event record from the database");
            $transaction->commit();
            return true;
        } catch (\Exception $e) {
            $transaction->rollBack();
            Yii::error("\nFailed delete event $id:\n" . $e->getMessage(), __METHOD__);
            Events::updateAll(['statuses_id' => Statuses::getStatusId(Statuses::ERROR)], ['id' => $event->id]);
            return false;
        }
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

    public function publishEvent(int|array $expertIds, string $message = ''): void
    {
        $channels = [Yii::$app->sse::EVENT_CHANNEL];
        $expertIds = (is_array($expertIds) ? $expertIds : [$expertIds]);
        foreach($expertIds as $id) { $channels[] = $this->getExpertChannel($id); }
        Yii::$app->sse->publishAll($channels, $message);
    }
}