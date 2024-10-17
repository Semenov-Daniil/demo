<?php

namespace app\controllers;

use app\models\ExpertsCompetencies;
use app\models\StudentsCompetencies;
use app\models\Users;
use app\models\UsersCompetencies;
use Yii;
use yii\filters\AccessControl;
use yii\web\Controller;

class ExpertController extends Controller
{
    public $defaultAction = 'settings';

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
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function actions()
    {
        return [
            'error' => [
                'class' => 'yii\web\ErrorAction',
            ],
            'captcha' => [
                'class' => 'yii\captcha\CaptchaAction',
                'fixedVerifyCode' => YII_ENV_TEST ? 'testme' : null,
            ],
        ];
    }

    /**
     * Displays settings page.
     *
     * @return string
     */
    public function actionSettings()
    {
        $model = new ExpertsCompetencies();

        if (Yii::$app->request->isAjax) {
            if ($model->load(Yii::$app->request->post()) && $model->addExpert()) {
                Yii::$app->session->setFlash('success', "Эксперт успешно добавлен.");
                $model = new ExpertsCompetencies();
            } else {
                Yii::$app->session->setFlash('error', "Не удалось добавить эксперта.");
            }
        }

        return $this->render('settings', [
            'model' => $model,
            'dataProvider' => $model->getDataProviderExperts(20),
        ]);
    }

    /**
     * Displays student page.
     *
     * @return string
     */
    public function actionStudents()
    {
        $model = new StudentsCompetencies(['scenario' => StudentsCompetencies::SCENARIOS_ADD_STUDENT]);

        if (Yii::$app->request->isAjax) {
            if (Yii::$app->user->can('expert') && $model->load(Yii::$app->request->post()) && $model->addStudent()) {
                Yii::$app->session->setFlash('success', "Студент успешно добавлен.");
                $model = new StudentsCompetencies(['scenario' => StudentsCompetencies::SCENARIOS_ADD_STUDENT]);
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
    public function actionModules()
    {
        return $this->render('modules');
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

    public function actionDeleteExpert()
    {
        if (Yii::$app->request->isAjax) {
            $user = new Users();
            $user->id = Yii::$app->request->post()['id'];
            if (Yii::$app->user->can('expert') && $user->deleteUser()) {
                Yii::$app->session->setFlash('success', "Эксперт успешно удален.");
            } else {
                Yii::$app->session->setFlash('error', "Не удалось удалить эксперта.");
            }
        }
    }

    public function actionDeleteStudent()
    {
        if (Yii::$app->request->isAjax) {
            $user = new Users();
            $user->id = Yii::$app->request->post()['id'];
            if (Yii::$app->user->can('expert') && $user->deleteUser()) {
                Yii::$app->session->setFlash('success', "Студент успешно удален.");
            } else {
                Yii::$app->session->setFlash('error', "Не удалось удалить студента.");
            }
        }
    }
}
