<?php

namespace backend\controllers;

use common\models\ExpertForm;
use common\models\Experts;
use common\models\Users;
use common\services\ExpertService;
use Yii;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\helpers\VarDumper;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\Response;

use function PHPUnit\Framework\isNull;

class ExpertController extends Controller
{
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
                    'all-experts' => ['GET'],
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
        $model = new ExpertForm();
        $dataProvider = Experts::getExpertsDataProvider(10);

        return $this->render('experts', [
            'model' => $model,
            'dataProvider' => $dataProvider,
        ]);
    }

    public function actionCreateExpert(): string
    {
        $model = new ExpertForm();
        $service = new ExpertService();

        if ($this->request->isPost && $model->load(Yii::$app->request->post()) && $service->createExpert($model)) {
            Yii::$app->session->addFlash('toastify', [
                'text' => 'Эксперт успешно добавлен.',
                'type' => 'success'
            ]);
            $model = new ExpertForm();
        } else {
            Yii::$app->session->addFlash('toastify', [
                'text' => 'Не удалось добавить эксперта.',
                'type' => 'error'
            ]);
        }

        Yii::$app->session->close();

        return $this->request->isAjax 
            ? $this->renderAjax('_expert-create', ['model' => $model])
            : $this->render('_expert-create', ['model' => $model]);
    }

    public function actionAllExperts(): string
    {
        $dataProvider = Experts::getExpertsDataProvider(10);
        Yii::$app->session->close();

        return $this->request->isAjax 
            ? $this->renderAjax('_experts-list', ['dataProvider' => $dataProvider])
            : $this->render('_experts-list', ['dataProvider' => $dataProvider]);
    }

    public function actionUpdateExpert(?string $id = null): Response|string
    {
        $model = $this->findExpertForm($id);
        $service = new ExpertService();

        if ($this->request->isPatch) {
            if ($model->load($this->request->post()) && $service->updateExpert($id, $model)) {
                Yii::$app->session->addFlash('toastify', [
                    'text' => 'Эксперт успешно обновлен.',
                    'type' => 'success'
                ]);
                Yii::$app->session->close();
                return $this->asJson(['success' => true]);
            } else {
                Yii::$app->session->addFlash('toastify', [
                    'text' => 'Не удалось обновить эксперта.',
                    'type' => 'error'
                ]);
            }
            
            Yii::$app->session->close();
        }


        return $this->request->isAjax 
            ? $this->renderAjax('_expert-update', ['model' => $model])
            : $this->render('_expert-update', ['model' => $model]);
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
        $service = new ExpertService();
        $experts = $id ? [$id] : ($this->request->post('experts') ?: []);
        $result = [];

        if ($experts && $result['success'] = $service->deleteExperts($experts)) {
            $result['message'] = 'Experts deleted.';
            Yii::$app->session->addFlash('toastify', [
                'text' => count($experts) > 1 ? 'Эксперты успешно удалены.' : 'Эксперт успешно удален.',
                'type' => 'success'
            ]);
        } else {
            $result['message'] = 'Experts not deleted.';
            Yii::$app->session->addFlash('toastify', [
                'text' => count($experts) > 1 ? 'Не удалось удалить экспертов.' : 'Не удалось удалить эксперта.',
                'type' => 'error'
            ]);
        }

        $result['code'] = Yii::$app->response->statusCode;
        return $this->asJson(['data' => $result]);
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
