<?php

namespace app\controllers;

use app\models\ExpertsCompetencies;
use app\models\Modules;
use app\models\StudentsCompetencies;
use app\models\Users;
use Yii;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\helpers\VarDumper;
use yii\web\Controller;
use yii\web\Response;

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
                'denyCallback' => function ($rule, $action) {
                    Yii::$app->user->isGuest ? $this->redirect(['login']) : $this->redirect(['/']);
                }
            ],
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'delete-experts' => ['DELETE'],
                    'delete-students' => ['DELETE'],
                    'change-status-modules' => ['PATH'],
                    'delete-modules' => ['DELETE'],
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
        $model = new ExpertsCompetencies();

        if (Yii::$app->request->isAjax && !is_null(Yii::$app->request->post('add'))) {
            if ($model->load(Yii::$app->request->post()) && $model->addExpert()) {
                Yii::$app->session->setFlash('success', "Эксперт успешно добавлен.");
                $model = new ExpertsCompetencies();
            } else {
                Yii::$app->session->setFlash('error', "Не удалось добавить эксперта.");
            }
        }

        return $this->render('experts', [
            'model' => $model,
            'dataProvider' => $model->getDataProviderExperts(20),
        ]);
    }

    /**
     * Action delete experts.
     *
     * @param string|null $id expert ID. 
     * 
     * @return void
     */
    public function actionDeleteExperts(string|null $id = null): void
    {
        if (Yii::$app->request->isAjax) {
            if (ExpertsCompetencies::deleteExpert($id)) {
                Yii::$app->session->setFlash('success', "Эксперт успешно удален.");
            } else {
                Yii::$app->session->setFlash('error', "Не удалось удалить эксперта.");
            }
        }
    }

    /**
     * Displays students page.
     *
     * @return string
     */
    public function actionStudents(): string
    {
        $model = new StudentsCompetencies(['scenario' => StudentsCompetencies::SCENARIO_ADD_STUDENT]);

        if (Yii::$app->request->isAjax && !is_null(Yii::$app->request->post('add'))) {
            if ($model->load(Yii::$app->request->post()) && $model->addStudent()) {
                Yii::$app->session->setFlash('success', "Студент успешно добавлен.");
                $model = new StudentsCompetencies(['scenario' => StudentsCompetencies::SCENARIO_ADD_STUDENT]);
            } else {
                Yii::$app->session->setFlash('error', "Не удалось добавить студента.");
            }
        }

        return $this->render('students', [
            'model' => $model,
            'dataProvider' => $model->getDataProviderStudents(20),
        ]);
    }

    /**
     * Action delete students.
     * 
     * @param string|null $id student ID. 
     *
     * @return void
     */
    public function actionDeleteStudents(string|null $id = null): void
    {
        if (Yii::$app->request->isAjax) {
            if (StudentsCompetencies::deleteStudent($id)) {
                Yii::$app->session->setFlash('success', "Студент успешно удален.");
            } else {
                Yii::$app->session->setFlash('error', "Не удалось удалить студента.");
            }
        }
    }

    /**
     * Displays files page.
     *
     * @return string
     */
    public function actionFiles()
    {
        return $this->render('files');
    }

    /**
     * Displays modules page.
     *
     * @return string
     */
    public function actionModules(): string
    {
        return $this->render('modules', [
            'dataProvider' => Modules::getDataProviderModules(),
        ]);
    }

    /**
     * Action change status modules.
     *
     * @return Response
     */
    public function actionChangeStatusModules(): Response
    {
        if (Yii::$app->request->isAjax && !is_null(Yii::$app->request->post('change'))) {
            $model = new Modules();
            if ($model->load(Yii::$app->request->post(), '')) {
                if ($model->changeStatus()) {
                    Yii::$app->response->statusCode = 200;
                    return $this->asJson(['success' => true]);
                } else {
                    Yii::$app->response->statusCode = 500;
                    return $this->asJson(['success' => false]);
                }
            }
        }
    }

    /**
     * Action delete modules.
     * 
     * @param string|null $id module ID. 
     *
     * @return void
     */
    public function actionDeleteModules(string|null $id = null): void
    {
        if (Yii::$app->request->isAjax) {
            if (Modules::deleteModule($id)) {
                Yii::$app->session->setFlash('success', "Модуль успешно удален.");
            } else {
                Yii::$app->session->setFlash('error', "Не удалось удалить модуль.");
            }
        }
    }

    /**
     * Displays competitors page.
     *
     * @return string
     */
    public function actionCompetitors()
    {
        return $this->render('competitors');
    }
}
