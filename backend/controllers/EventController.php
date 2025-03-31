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

class EventController extends Controller
{
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
                    'all-events' => ['GET'],
                    'update-event' => ['GET', 'PATCH'],
                    'delete-events' => ['DELETE'],
                ],
            ],
        ];
    }

    public function getExperts()
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
        $model = new EventForm();
        $dataProvider = Events::getDataProviderEvents(Yii::$app->user->id);

        return $this->render('events', [
            'model' => $model,
            'dataProvider' => $dataProvider,
            'experts' => $this->experts,
        ]);
    }

    public function actionCreateEvent(): string
    {
        $model = new EventForm();
        $service = new EventService();

        if ($this->request->isPost && $model->load(Yii::$app->request->post()) && $service->createEvent($model)) {
            Yii::$app->session->addFlash('toastify', [
                'text' => 'Чемпионат успешно создан.',
                'type' => 'success'
            ]);

            $model = new EventForm();
        } else {
            Yii::$app->session->addFlash('toastify', [
                'text' => 'Не удалось создать чемпионат.',
                'type' => 'error'
            ]);
        }

        Yii::$app->session->close();

        return $this->request->isAjax 
            ? $this->renderAjax('_event-create', ['model' => $model, 'experts' => $this->experts])
            : $this->render('_event-create', ['model' => $model, 'experts' => $this->experts]);
    }

    public function actionAllEvents(): string
    {
        $dataProvider = Events::getDataProviderEvents(Yii::$app->user->id);

        session_write_close();

        if ($this->request->isAjax) {
            return $this->renderAjax('_events-list', [
                'dataProvider' => $dataProvider,
            ]);
        }

        return $this->render('_events-list', [
            'dataProvider' => $dataProvider,
        ]);
    }

    public function actionUpdateEvent(?string $id = null): Response|string
    {
        $model = $this->findEvent($id);
        $model->scenario = Events::SCENARIO_UPDATE;

        if ($this->request->isPatch) {
            if ($model->load($this->request->post()) && $model->save()) {
                Yii::$app->session->addFlash('toastify', [
                    'text' => 'Чемпионат успешно обновлен.',
                    'type' => 'success'
                ]);

                return $this->asJson([
                    'success' => true
                ]);
            } else {
                Yii::$app->session->addFlash('toastify', [
                    'text' => 'Не удалось обновить чемпионат.',
                    'type' => 'error'
                ]);
            }
        }

        if ($this->request->isAjax) {
            return $this->renderAjax('_event-update', [
                'model' => $model,
                'experts' => Experts::getExperts(),
            ]);
        }

        return $this->render('_event-update', [
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
        $dataProvider = Events::getDataProviderEvents(Yii::$app->user->id);
        $events = [];

        $events = (!is_null($id) ? [$id] : ($this->request->post('events') ? $this->request->post('events') : []));

        if (count($events) && Events::deleteEvents($events)) {
            Yii::$app->session->addFlash('toastify', [
                'text' => count($events) > 1 ? 'Чемпионаты успешно удалены.' : 'Чемпионат успешно удален.',
                'type' => 'success'
            ]);
        } else {
            Yii::$app->session->addFlash('toastify', [
                'text' => count($events) > 1 ? 'Не удалось удалить чемпионаты.' : 'Не удалось удалить чемпионат.',
                'type' => 'error'
            ]);
        }

        if ($this->request->isAjax) {
            return $this->renderAjax('_events-list', [
                'dataProvider' => $dataProvider,
            ]);
        }

        return $this->render('_events-list', [
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * Finds an event model by its ID.
     *
     * @param mixed $id The ID of the event to find.
     * @return Events The event model found.
     * @throws NotFoundHttpException If the event is not found.
     */
    protected function findEvent($id)
    {
        if (($model = Events::findOne(['id' => $id])) !== null) {
            return $model;
        }

        Yii::$app->session->addFlash('toastify', [
            'text' => 'Чемпионат не найден.',
            'type' => 'error'
        ]);

        throw new NotFoundHttpException('Чемпионат не найден.');
    }
}
