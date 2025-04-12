<?php

namespace backend\controllers;

use common\models\Events;
use common\models\Modules;
use common\services\ModuleService;
use Yii;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\Response;

class ModuleController extends BaseController
{
    private ModuleService $moduleService;

    public function __construct($id, $module, $config = [])
    {
        parent::__construct($id, $module, $config);
        $this->moduleService = new ModuleService();
    }

    public $defaultAction = 'modules';

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
                    'modules' => ['GET'],
                    'create-module' => ['POST'],
                    'list-modules' => ['GET'],
                    'change-status-module' => ['PATH'],
                    'delete-modules' => ['DELETE'],
                    'clear-modules' => ['PATH'],
                ],
            ],
        ];
    }

    private function getEvents()
    {
        return Yii::$app->user->can('sExpert') ? Events::getExpertEvents() : Events::getEvents(Yii::$app->user->id);
    }

    /**
     * Displays modules page.
     *
     * @return string
     */
    public function actionModules(?int $event = null): string
    {
        return $this->render('modules', [
            'model' => new Modules(['events_id' => $event]),
            'dataProvider' => Modules::getDataProviderModules($event),
            'events' => $this->getEvents(),
            'event' => $this->findEvent($event),
        ]);
    }

    public function actionListModules(?int $event = null): string
    {
        return $this->renderAjaxIfRequested('_modules-list', [
            'dataProvider' => Modules::getDataProviderModules($event),
            'event' => $this->findEvent($event),
        ]);
    }

    public function actionCreateModule()
    {
        $model = new Modules();

        if ($this->request->isPost && $model->load($this->request->post())) {
            $success = $this->moduleService->createModule($model);

            $this->addToastMessage(
                $success ? 'Модуль успешно создан.' : 'Не удалось создать модуль.',
                $success ? 'success' : 'error'
            );

            if ($success) {
                $model = new Modules(['events_id' => $model->events_id]);
            }
        }

        return $this->renderAjaxIfRequested('_module-create', [
            'model' => $model,
            'events' => $this->getEvents(),
        ]);
    }

    /**
     * Action change status module.
     */
    public function actionChangeStatusModule(?int $id)
    {
        $status = Yii::$app->request->post('newStatus');
        $model = $this->findModule($id);

        $success = $this->moduleService->changeStatus($model, $status);

        $this->addToastMessage(
            $success ? "Модуль {$model->number} ". ($model->status ? 'включен' : 'выключен') : 'Не удалось ' . (!$status ? 'включить' : 'выключить') . " модуль {$model?->number}.",
            $success ? 'info' : 'error'
        );

        return $this->asJson([
            'success' => $success,
            'code' => Yii::$app->response->statusCode,
            'module' => [
                'status' => $model?->status,
            ]
        ]);
    }

    /**
     * Action delete modules.
     * 
     * @param string|null $id module ID. 
     *
     * @return void
     */
    public function actionDeleteModules(?string $id = null): Response
    {
        $modules = $id ? [$id] : ($this->request->post('modules') ?: []);
        $count = count($modules);
        $result = [];

        $result['success'] = $count && $this->moduleService->deleteModules($modules);
        $result['message'] = $result['success'] ? 'Modules deleted.' : 'Modules not deleted.';

        $this->addToastMessage(
            $result['success'] 
                ? ($count > 1 ? 'Модули успешно удалены.' : 'Модуль успешно удален.') 
                : ($count > 1 ? 'Не удалось удалить модули.' : 'Не удалось удалить модуль.'),
            $result['success'] ? 'success' : 'error'
        );

        $result['code'] = Yii::$app->response->statusCode;
        return $this->asJson($result);
    }

    public function actionClearModules(?string $id = null): Response
    {
        $modules = $id ? [$id] : ($this->request->post('modules') ?: []);
        $count = count($modules);
        $result = [];

        $result['success'] = $count && $this->moduleService->clearModules($modules);
        $result['message'] = $result['success'] ? 'Modules cleared.' : 'Modules not cleared.';

        $this->addToastMessage(
            $result['success'] 
                ? ($count > 1 ? 'Модули успешно очищены.' : 'Модуль успешно очищен.') 
                : ($count > 1 ? 'Не удалось очистить модули.' : 'Не удалось очистить модуль.'),
            $result['success'] ? 'success' : 'error'
        );

        $result['code'] = Yii::$app->response->statusCode;
        return $this->asJson($result);
    }

    protected function findModule(?int $id): ?Modules
    {
        if ($id && ($model = Modules::findOne(['id' => $id])) !== null) {
            return $model;
        }

        $this->addToastMessage('Модул не найден.', 'error');

        throw new NotFoundHttpException('Модуль не найден.');
    }

    protected function findEvent(?int $id): ?Events
    {
        return Events::findOne(['id' => $id]);
    }
}
