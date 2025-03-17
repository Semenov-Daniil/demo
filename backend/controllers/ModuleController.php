<?php

namespace backend\controllers;

use common\models\EncryptedPasswords;
use common\models\Events;
use common\models\EventForm;
use common\models\Experts;
use common\models\ExpertsEvents;
use common\models\ExpertsForm;
use common\models\FilesEvents;
use common\models\LoginForm;
use common\models\Modules;
use common\models\Passwords;
use common\models\Roles;
use common\models\Students;
use common\models\Users;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\TemplateProcessor;
use Yii;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\helpers\VarDumper;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\Response;
use yii\web\UploadedFile;

class ModuleController extends Controller
{
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
                    'change-status-module' => ['PATH'],
                    'delete-modules' => ['DELETE'],
                    'clear-modules' => ['PATH'],
                ],
            ],
        ];
    }

    /**
     * Displays modules page.
     *
     * @return string
     */
    public function actionModules(?int $event = null): string
    {
        $model = new Modules();
        $dataProvider = Modules::getDataProviderModules($event);

        return $this->render('modules', [
            'model' => $model,
            'dataProvider' => $dataProvider,
            'events' => Yii::$app->user->can('sExpert') ? Events::getExpertEvents() : Events::getEvents(Yii::$app->user->id),
            'event' => Events::findOne(['id' => $event]),
        ]);
    }

    public function actionAllModules(?int $event = null): string
    {
        $dataProvider = Modules::getDataProviderModules($event);
        $event = Events::findOne(['id' => $event]);

        session_write_close();

        if ($this->request->isAjax) {
            return $this->renderAjax('_modules-list', [
                'dataProvider' => $dataProvider,
                'event' => $event
            ]);
        }

        return $this->render('_modules-list', [
            'dataProvider' => $dataProvider,
            'event' => $event
        ]);
    }

    public function actionCreateModule()
    {
        $model = new Modules();

        if ($this->request->isPost) {
            if ($model->load($this->request->post()) && $model->createModule()) {
                Yii::$app->session->addFlash('toastify', [
                    'text' => 'Модуль успешно создан.',
                    'type' => 'success'
                ]);
                $model = new Modules(['events_id' => $model?->events_id]);
            } else {
                Yii::$app->session->addFlash('toastify', [
                    'text' => 'Не удалось создать модуль.',
                    'type' => 'error'
                ]);
            }
        }

        if ($this->request->isAjax) {
            return $this->renderAjax('_module-create', [
                'model' => $model,
                'events' => Yii::$app->user->can('sExpert') ? Events::getExpertEvents() : Events::getEvents(Yii::$app->user->id),
            ]);
        }

        return $this->render('_module-create', [
            'model' => $model,
            'events' => Yii::$app->user->can('sExpert') ? Events::getExpertEvents() : Events::getEvents(Yii::$app->user->id),
        ]);
    }

    /**
     * Action change status module.
     */
    public function actionChangeStatusModule()
    {
        $id = Yii::$app->request->post('id');
        $status = Yii::$app->request->post('newStatus');
        $isChangeStatus = false;
        $model = $this->findModule($id);

        try {
            $isChangeStatus = $model->changeStatus($status);
        } catch (\Exception $e) {
        }

        if ($isChangeStatus) {
            Yii::$app->session->addFlash('toastify', [
                'text' => "Модуль $model->number " . ($model->status ? 'включен' : 'выключен') . '.',
                'type' => 'info'
            ]);
        } else {
            Yii::$app->session->addFlash('toastify', [
                'text' => "Не удалось " . (!$status ? 'включить' : 'выключить') . " модуль $model?->number.",
                'type' => 'error'
            ]);
        }
        
        if ($this->request->isAjax) {
            return $this->asJson([
                'data' => [
                    'success' => $isChangeStatus,
                    'module' => [
                        'id' => $model?->id,
                        'status' => $model?->status,
                    ]
                ],
            ]);
        }

        return $this->actionModules();
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
        $modules = [];
        $result = [];

        $modules = (!is_null($id) ? [$id] : ($this->request->post('modules') ? $this->request->post('modules') : []));

        if (count($modules) && $result['success'] = Modules::deleteModules($modules)) {
            $result['message'] = 'Modules delete.';
            Yii::$app->session->addFlash('toastify', [
                'text' => count($modules) > 1 ? 'Модули успешно удалены.' : 'Модуль успешно удален.',
                'type' => 'success'
            ]);
        } else {
            $result['message'] = 'Modules not deleted.';
            Yii::$app->session->addFlash('toastify', [
                'text' => count($modules) > 1 ? 'Не удалось удалить модули.' : 'Не удалось удалить модуль.',
                'type' => 'error'
            ]);
        }

        $result['code'] = Yii::$app->response->statusCode;

        return $this->asJson([
            'data' => $result
        ]);
    }

    public function actionClearModules(?string $id = null): Response
    {
        $modules = [];
        $result = [];

        $modules = (!is_null($id) ? [$id] : ($this->request->post('modules') ? $this->request->post('modules') : []));

        if (count($modules) && $result['success'] = Modules::clearModules($modules)) {
            $result['message'] = 'Modules cleare.';
            Yii::$app->session->addFlash('toastify', [
                'text' => count($modules) > 1 ? 'Модули успешно очищены.' : 'Модуль успешно очищен.',
                'type' => 'success'
            ]);
        } else {
            $result['message'] = 'Modules not cleared.';
            Yii::$app->session->addFlash('toastify', [
                'text' => count($modules) > 1 ? 'Не удалось очистить модули.' : 'Не удалось очистить модуль.',
                'type' => 'error'
            ]);
        }

        $result['code'] = Yii::$app->response->statusCode;

        return $this->asJson([
            'data' => $result
        ]);
    }

    protected function findModule($id)
    {
        if (($model = Modules::findOne(['id' => $id])) !== null) {
            return $model;
        }

        Yii::$app->session->addFlash('toastify', [
            'text' => "Модуль не найден.",
            'type' => 'error'
        ]);

        throw new NotFoundHttpException('Модуль не найден.');
    }
}
