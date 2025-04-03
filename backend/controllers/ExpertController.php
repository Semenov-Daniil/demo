<?php

namespace backend\controllers;

use common\models\ExpertForm;
use common\models\Experts;
use common\models\Users;
use common\services\ExpertService;
use Yii;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\helpers\Html;
use yii\helpers\VarDumper;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\Response;

use function PHPUnit\Framework\isNull;

class ExpertController extends BaseController
{
    private ExpertService $expertService;

    public function __construct($id, $module, $config = [])
    {
        parent::__construct($id, $module, $config);
        $this->expertService = new ExpertService();
    }

    public $defaultAction = 'experts';

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
                    'experts' => ['GET'],
                    'create-expert' => ['POST'],
                    'list-experts' => ['GET'],
                    'update-expert' => ['GET', 'PATCH'],
                    'delete-experts' => ['DELETE'],
                ],
            ],
        ];
    }

    /**
     * Displays experts page.
     *
     * @return string
     */
    public function actionExperts(): string
    {
        return $this->render('experts', [
            'model' => new ExpertForm(),
            'dataProvider' => Experts::getExpertsDataProvider(),
        ]);
    }

    public function actionCreateExpert(): string
    {
        $model = new ExpertForm();

        if ($this->request->isPost && $model->load(Yii::$app->request->post())) {
            $success = $this->expertService->createExpert($model);

            $this->addFlashMessage(
                $success ? 'Эксперт успешно добавлен.' : 'Не удалось добавить эксперта.',
                $success ? 'success' : 'error'
            );

            if ($success) {
                $model = new ExpertForm();
            }
        }

        return $this->renderAjaxIfRequested('_expert-create', ['model' => $model]);
    }

    public function actionListExperts(): string
    {
        return $this->renderAjaxIfRequested('_experts-list', [
            'dataProvider' => Experts::getExpertsDataProvider(),
        ]);
    }

    public function actionUpdateExpert(?string $id = null): Response|string
    {
        $model = $this->findExpertForm($id);
        $result = ['success' => false];

        if ($this->request->isPatch && $model->load($this->request->post())) {
            $result['success'] = $this->expertService->updateExpert($id, $model);

            $this->addFlashMessage(
                $result['success'] ? 'Эксперт успешно обновлен.' : 'Не удалось обновить эксперта.',
                $result['success'] ? 'success' : 'error'
            );

            $result['errors'] = [];
            foreach ($model->getErrors() as $attribute => $errors) {
                $result['errors'][Html::getInputId($model, $attribute)] = $errors;
            }

            return $this->asJson($result);
        }

        return $this->renderAjaxIfRequested('_expert-update', ['model' => $model]);
    }

    /**
     * Action delete experts.
     *
     * @param string $id expert ID. 
     * 
     * @return void
     */
    public function actionDeleteExperts(?string $id = null): Response
    {
        $experts = $id ? [$id] : ($this->request->post('experts') ?: []);
        $count = count($experts);
        $result = [];

        if ($experts && $result['success'] = $this->expertService->deleteExperts($experts)) {
            $result['message'] = 'Experts deleted.';
            $this->addFlashMessage(
                $count > 1 ? 'Эксперты успешно удалены.' : 'Эксперт успешно удален.',
                'success'
            );
        } else {
            $result['message'] = 'Experts not deleted.';
            $this->addFlashMessage(
                $count > 1 ? 'Не удалось удалить экспертов.' : 'Не удалось удалить эксперта.',
                'error'
            );
        }

        $result['code'] = Yii::$app->response->statusCode;
        return $this->asJson($result);
    }

    protected function findExpertForm(?string $id): ExpertForm
    {
        if ($user = Users::findOne($id)) {
            $model = new ExpertForm();
            $model->surname = $user->surname;
            $model->name = $user->name;
            $model->patronymic = $user->patronymic;
            return $model;
        }

        Yii::$app->session->addFlash('toastify', [
            'text' => "Эксперт не найден.",
            'type' => 'error'
        ]);
        throw new NotFoundHttpException('Эксперт не найден.');
    }
}
