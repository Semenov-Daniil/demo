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
        $dataProvider = Experts::getExpertsDataProvider(Yii::$app->request->get('page', 0));

        return $this->render('experts', [
            'model' => new ExpertForm(),
            'dataProvider' => $dataProvider,
        ]);
    }

    public function actionCreateExpert(): string|Response
    {
        $model = new ExpertForm();
        $result = ['success' => false];

        if ($this->request->isPost && $model->load(Yii::$app->request->post())) {
            $result['success'] = $this->expertService->createExpert($model);

            Yii::$app->toast->addToast(
                $result['success'] ? 'Эксперт успешно добавлен.' : 'Не удалось добавить эксперта.',
                $result['success'] ? 'success' : 'error'
            );

            if ($result['success']) Yii::$app->sse->publish(Yii::$app->sse::EXPERT_CHANNEL, 'create-expert');

            $result['errors'] = [];
            foreach ($model->getErrors() as $attribute => $errors) {
                $result['errors'][Html::getInputId($model, $attribute)] = $errors;
            }

            return $this->asJson($result);
        }

        return $this->renderAjaxIfRequested('_expert-create', ['model' => $model]);
    }

    public function actionListExperts(): string
    {
        $dataProvider = Experts::getExpertsDataProvider(Yii::$app->request->get('page', 0));
        return $this->renderAjaxIfRequested('_experts-list', [
            'dataProvider' => $dataProvider,
        ]);
    }

    public function actionUpdateExpert(?string $id = null): Response|string
    {
        $model = $this->findExpertForm($id);
        $result = ['success' => false];

        if ($this->request->isPatch && $model->load($this->request->post())) {
            $result['success'] = $this->expertService->updateExpert($id, $model);

            Yii::$app->toast->addToast(
                $result['success'] ? 'Эксперт успешно обновлен.' : 'Не удалось обновить эксперта.',
                $result['success'] ? 'success' : 'error'
            );

            $result['errors'] = [];
            foreach ($model->getErrors() as $attribute => $errors) {
                $result['errors'][Html::getInputId($model, $attribute)] = $errors;
            }

            if ($result['success']) Yii::$app->sse->publish(Yii::$app->sse::EXPERT_CHANNEL, 'update');

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

        $result['success'] = $count && $this->expertService->deleteExperts($experts);
        $result['message'] = $result['success'] ? 'Experts deleted.' : 'Experts not deleted.';

        Yii::$app->toast->addToast(
            $result['success'] 
                ? ($count > 1 ? 'Эксперты успешно удалены.' : 'Эксперт успешно удален.') 
                : ($count > 1 ? 'Не удалось удалить экспертов.' : 'Не удалось удалить эксперта.'),
            $result['success'] ? 'success' : 'error'
        );

        if ($result['success']) Yii::$app->sse->publish(Yii::$app->sse::EXPERT_CHANNEL, 'update');

        $result['code'] = Yii::$app->response->statusCode;
        return $this->asJson($result);
    }

    public function actionSseDataUpdates()
    {
        Yii::$app->sse->subscriber(Yii::$app->sse::EXPERT_CHANNEL);
    }

    protected function findExpertForm(?string $id): ExpertForm
    {
        if ($user = Users::findOne($id)) {
            $model = new ExpertForm();
            $model->surname = $user->surname;
            $model->name = $user->name;
            $model->patronymic = $user->patronymic;
            $model->updated_at = $user->updated_at;
            return $model;
        }

        Yii::$app->toast->addToast('Эксперт не найден.', 'error');

        throw new NotFoundHttpException('Эксперт не найден.');
    }
}
