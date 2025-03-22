<?php

namespace backend\controllers;

use common\models\Experts;
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
        $model = new Experts();
        $dataProvider = $model->getDataProviderExperts(10);

        return $this->render('experts', [
            'model' => $model,
            'dataProvider' => $dataProvider,
        ]);
    }

    public function actionCreateExpert(): string
    {
        $model = new Experts();

        if ($this->request->isPost) {
            if ($model->load(Yii::$app->request->post()) && $model->createExpert()) {  
                Yii::$app->session->addFlash('toastify', [
                    'text' => 'Эксперт успешно добавлен.',
                    'type' => 'success'
                ]);

                $model = new Experts();
            } else {
                Yii::$app->session->addFlash('toastify', [
                    'text' => 'Не удалось добавить эксперта.',
                    'type' => 'error'
                ]);
            }
        }

        if ($this->request->isAjax) {
            return $this->renderAjax('_expert-create', [
                'model' => $model,
            ]);
        }

        return $this->render('_expert-create', [
            'model' => $model,
        ]);
    }

    public function actionAllExperts(): string
    {
        $dataProvider = Experts::getDataProviderExperts(10);

        session_write_close();

        if ($this->request->isAjax) {
            return $this->renderAjax('_experts-list', [
                'dataProvider' => $dataProvider,
            ]);
        }

        return $this->render('_experts-list', [
            'dataProvider' => $dataProvider,
        ]);
    }

    public function actionUpdateExpert(?string $id = null): Response|string
    {
        $model = $this->findExpert($id);

        if ($this->request->isPatch) {
            if ($model->load($this->request->post()) && $model->updateExpert($id)) {
                Yii::$app->session->addFlash('toastify', [
                    'text' => 'Эксперт успешно обновлен.',
                    'type' => 'success'
                ]);

                return $this->asJson([
                    'success' => true
                ]);
            } else {
                Yii::$app->session->addFlash('toastify', [
                    'text' => 'Не удалось обновить эксперта.',
                    'type' => 'error'
                ]);
            }
        }

        if ($this->request->isAjax) {
            return $this->renderAjax('_expert-update', [
                'model' => $model,
            ]);
        }

        return $this->render('_expert-update', [
            'model' => $model,
        ]);
    }

    /**
     * Action delete experts.
     *
     * @param string $id expert ID. 
     * 
     * @return void
     */
    public function actionDeleteExperts(?string $id = null): string
    {
        $dataProvider = Experts::getDataProviderExperts(10);
        $experts = [];

        $experts = (!is_null($id) ? [$id] : ($this->request->post('experts') ? $this->request->post('experts') : []));

        if (count($experts) && Experts::deleteExperts($experts)) {
            Yii::$app->session->addFlash('toastify', [
                'text' => count($experts) > 1 ? 'Эксперты успешно удалены.' : 'Эксперт успешно удален.',
                'type' => 'success'
            ]);
        } else {
            Yii::$app->session->addFlash('toastify', [
                'text' => count($experts) > 1 ? 'Не удалось удалить экспертов.' : 'Не удалось удалить эксперта.',
                'type' => 'error'
            ]);
        }

        if ($this->request->isAjax) {
            return $this->renderAjax('experts/_experts-list', [
                'dataProvider' => $dataProvider,
            ]);
        }

        return $this->render('experts/_experts-list', [
            'dataProvider' => $dataProvider,
        ]);
    }

    protected function findExpert($id)
    {
        if (($model = Experts::findExpert($id)) !== null) {
            return $model;
        }

        Yii::$app->session->addFlash('toastify', [
            'text' => 'Эксперт не найден.',
            'type' => 'error'
        ]);

        throw new NotFoundHttpException('Эксперт не найден.');
    }
}
