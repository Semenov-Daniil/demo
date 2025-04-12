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
        $form = new EventForm();

        if ($this->request->isPost && $form->load(Yii::$app->request->post())) {
            $success = $this->eventService->createEvent($form);

            $this->addToastMessage(
                $success ? 'Чемпионат успешно создан.' : 'Не удалось создать чемпионат.',
                $success ? 'success' : 'error'
            );

            if ($success) {
                $form = new EventForm();
            }
        }

        return $this->renderAjaxIfRequested('_event-create', ['model' => $form, 'experts' => $this->getExperts()]);
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
        $result = ['success' => false];

        if ($this->request->isPatch && $model->load($this->request->post())) {
            $result['success'] = $model->save();

            $this->addToastMessage(
                $result['success'] ? 'Чемпионат успешно обновлен.' : 'Не удалось обновить чемпионат.',
                $result['success'] ? 'success' : 'error'
            );

            $result['errors'] = [];
            foreach ($model->getErrors() as $attribute => $errors) {
                $result['errors'][Html::getInputId($model, $attribute)] = $errors;
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

        $this->addToastMessage(
            $result['success'] 
                ? ($count > 1 ? 'Чемпионаты успешно удалены.' : 'Чемпионат успешно удален.') 
                : ($count > 1 ? 'Не удалось удалить чемпионаты.' : 'Не удалось удалить чемпионат.'),
            $result['success'] ? 'success' : 'error'
        );

        $result['code'] = Yii::$app->response->statusCode;
        return $this->asJson($result);
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

        $this->addToastMessage('Чемпионат не найден.', 'error');

        throw new NotFoundHttpException('Чемпионат не найден.');
    }
}
