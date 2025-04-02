<?php

namespace backend\controllers;

use common\models\Events;
use common\models\EventForm;
use common\models\Experts;
use common\services\EventService;
use Yii;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
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

    public function actionCreateEvent(): string
    {
        $model = new EventForm();

        if ($this->request->isPost && $model->load(Yii::$app->request->post())) {
            $success = $this->eventService->createEvent($model);

            $this->addFlashMessage(
                $success ? 'Чемпионат успешно создан.' : 'Не удалось создать чемпионат.',
                $success ? 'success' : 'error'
            );

            if ($success) {
                $model = new EventForm();
            }
        }

        return $this->renderAjaxIfRequested('_event-create', ['model' => $model, 'experts' => $this->getExperts()]);
    }

    public function actionListEvents(): string
    {
        return $this->renderAjaxIfRequested('_events-list', [
            'dataProvider' => Events::getDataProviderEvents(Yii::$app->user->id),
        ]);
    }

    public function actionUpdateEvent(?int $id = null): Response|string
    {
        $model = $this->findEvent($id);
        $model->scenario = Events::SCENARIO_UPDATE;

        if ($this->request->isPatch && $model->load($this->request->post())) {
            $success = $model->save();

            $this->addFlashMessage(
                $success ? 'Чемпионат успешно обновлен.' : 'Не удалось обновить чемпионат.',
                $success ? 'success' : 'error'
            );

            if ($success) {
                return $this->asJson(['success' => true]);
            }
        }

        if ($this->request->isAjax) {
            return $this->renderAjax('_event-update', [
                'model' => $model,
                'experts' => Experts::getExperts(),
            ]);
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
    public function actionDeleteEvents(?string $id = null): string
    {
        $events = $id ? [$id] : (array) $this->request->post('events', []);
        $count = count($events);

        if ($count && $this->eventService->deleteEvents($events)) {
            $this->addFlashMessage(
                $count > 1 ? 'Чемпионаты успешно удалены.' : 'Чемпионат успешно удален.',
                'success'
            );
        } else {
            $this->addFlashMessage(
                $count > 1 ? 'Не удалось удалить чемпионаты.' : 'Не удалось удалить чемпионат.',
                'error'
            );
        }

        return $this->renderAjaxIfRequested('_events-list', [
            'dataProvider' => Events::getDataProviderEvents(Yii::$app->user->id),
        ]);
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

        $this->addFlashMessage('Чемпионат не найден.', 'error');

        throw new NotFoundHttpException('Чемпионат не найден.');
    }
}
