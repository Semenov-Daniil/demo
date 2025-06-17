<?php

namespace backend\controllers;

use common\models\Events;
use common\models\EventForm;
use common\models\Experts;
use common\services\EventService;
use Yii;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\helpers\Html;
use yii\helpers\VarDumper;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\Response;

class EventController extends BaseController
{
    private EventService $eventService;

    public function __construct($id, $module, $config = [])
    {
        parent::__construct($id, $module, $config);
        $this->eventService = new EventService();
    }

    public $defaultAction = 'events';

    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    [
                        'allow' => true,
                        'roles' => ['expert'],
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'events' => ['GET'],
                    'create-event' => ['POST'],
                    'all-experts' => ['GET'],
                    'list-events' => ['GET'],
                    'update-event' => ['GET', 'PATCH'],
                    'delete-events' => ['DELETE'],
                ],
            ],
        ];
    }

    private function getExperts()
    {
        return Yii::$app->user->can('sExpert') ? Experts::getExperts() : [];
    }

    /**
     * Displays events page.
     *
     * @return string
     */
    public function actionEvents(): string
    {
        return $this->render('events', [
            'model' => new EventForm(),
            'dataProvider' => Events::getDataProviderEvents(Yii::$app->user->id),
            'experts' => $this->getExperts(),
        ]);
    }

    public function actionCreateEvent(): string|Response
    {
        $form = new EventForm();
        $result = ['success' => false];

        if ($this->request->isPost && $form->load(Yii::$app->request->post())) {
            $result['success'] = $this->eventService->createEvent($form);

            Yii::$app->toast->addToast(
                $result['success'] ? 'Событие успешно создан.' : 'Не удалось создать событие.',
                $result['success'] ? 'success' : 'error'
            );

            if ($result['success']) {
                $expertId = (Yii::$app->user->can('sExpert') ? $form->expert : Yii::$app->user->id);
                $this->publishEvent($expertId, 'create-event');
            }

            $result['errors'] = [];
            foreach ($form->getErrors() as $attribute => $errors) {
                $result['errors'][Html::getInputId($form, $attribute)] = $errors;
            }

            return $this->asJson($result);
        }

        return $this->renderAjaxIfRequested('_event-create', ['model' => $form, 'experts' => $this->getExperts()]);
    }

    public function actionAllExperts()
    {
        $expertList = $this->getExperts();
        return $this->asJson(array_map(function($id, $name) {
            return ['value' => $id, 'label' => $name];
        }, array_keys($expertList), $expertList));
    }

    public function actionListEvents(): string
    {
        return $this->renderAjaxIfRequested('_events-list', [
            'dataProvider' => Events::getDataProviderEvents(Yii::$app->user->id),
        ]);
    }

    public function actionUpdateEvent(?int $id = null): Response|string
    {
        $model = $this->findEventForm($id);
        $model->scenario = $model::SCENARIO_UPDATE;
        $result = ['success' => false];
        $expert = $model->expertUpdate;

        if ($this->request->isPatch && $model->load($this->request->post())) {
            $result['success'] = $this->eventService->updateEvent($id, $model);

            Yii::$app->toast->addToast(
                $result['success'] ? 'Событие успешно обновлено.' : 'Не удалось обновить событие.',
                $result['success'] ? 'success' : 'error'
            );

            $result['errors'] = [];
            foreach ($model->getErrors() as $attribute => $errors) {
                $result['errors'][Html::getInputId($model, $attribute)] = $errors;
            }

            if ($result['success']) {
                if ($expert != $model->expertUpdate) $this->publishEvent($expert, 'update-event');
                $this->publishEvent($model->expert ?: $model->expertUpdate, 'update-event');
            }

            return $this->asJson($result);
        }

        return $this->renderAjaxIfRequested('_event-update', [
            'model' => $model,
            'experts' => Experts::getExperts(),
        ]);
    }

    /**
     * Action delete events.
     *
     * @param string $id expert ID. 
     * 
     * @return void
     */
    public function actionDeleteEvents(?string $id = null): Response
    {
        $events = $id ? [$id] : (array) $this->request->post('events', []);
        $count = count($events);
        $result = [];

        $result['success'] = $count && $this->eventService->deleteEvents($events);
        $result['message'] = $result['success'] ? 'Events deleted.' : 'Experts not deleted.';

        Yii::$app->toast->addToast(
            $result['success'] 
                ? ($count > 1 ? 'События успешно удалены.' : 'Событие успешно удалено.') 
                : ($count > 1 ? 'Не удалось удалить события.' : 'Не удалось удалить событие.'),
            $result['success'] ? 'success' : 'error'
        );

        $result['code'] = Yii::$app->response->statusCode;
        return $this->asJson($result);
    }

    public function actionSseDataUpdates()
    {
        if (Yii::$app->user->can('sExpert')) {
            Yii::$app->sse->subscriber(Yii::$app->sse::EVENT_CHANNEL);
        } else {
            Yii::$app->sse->subscriber($this->eventService->getExpertChannel(Yii::$app->user->id));
        }
    }

    protected function publishEvent(int|array $expertIds, string $message = ''): void
    {
        $channels = [Yii::$app->sse::EVENT_CHANNEL];
        $expertIds = (is_array($expertIds) ? $expertIds : [$expertIds]);
        foreach($expertIds as $id) { $channels[] = $this->eventService->getExpertChannel($id); }
        Yii::$app->sse->publishAll($channels, $message);
    }

    /**
     * Finds an event model by its ID.
     *
     * @param ?string $id The ID of the event to find.
     * @return Events The event model found.
     * @throws NotFoundHttpException If the event is not found.
     */
    protected function findEvent(?int $id): Events
    {
        if ($id && ($model = Events::findOne(['id' => $id])) !== null) {
            return $model;
        }

        Yii::$app->toast->addToast('Событие не найдено.', 'error');

        throw new NotFoundHttpException('Событие не найдено.');
    }

    protected function findEventForm(?string $id): EventForm
    {
        if ($event = $this->findEvent($id)) {
            $model = new EventForm();
            $model->title = $event->title;
            $model->expertUpdate = $event->experts_id;
            $model->updated_at = $event->updated_at;
            return $model;
        }

        Yii::$app->toast->addToast('Событие не найдено.', 'error');

        throw new NotFoundHttpException('Эксперт не найден.');
    }
}
