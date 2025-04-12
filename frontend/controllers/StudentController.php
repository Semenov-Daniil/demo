<?php

namespace frontend\controllers;

use common\models\EncryptedPasswords;
use common\models\Events;
use common\models\FilesEvents;
use Yii;
use yii\web\Controller;
use yii\filters\VerbFilter;
use yii\filters\AccessControl;
use common\models\LoginForm;
use common\models\Passwords;
use common\models\Roles;
use common\models\Students;
use common\models\Users;

/**
 * Site controller
 */
class StudentController extends Controller
{
    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    [
                        'actions' => ['index', 'logout', 'error'],
                        'allow' => true,
                        'roles' => ['student'],
                    ],
                    [
                        'actions' => ['login'],
                        'allow' => true,
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'logout' => ['post'],
                ],
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
                'class' => \yii\web\ErrorAction::class,
            ],
            'captcha' => [
                'class' => \yii\captcha\CaptchaAction::class,
                'fixedVerifyCode' => YII_ENV_TEST ? 'testme' : null,
            ],
        ];
    }

    /**
     * Displays homepage.
     *
     * @return mixed
     */
    public function actionIndex()
    {
        $student = Students::findOne(['students_id' => Yii::$app->user->id]);
        $files = FilesEvents::getDataProviderFiles($student->event->id);
        $modules = Events::findOne(['id' => $student->event->id])->modules;

        return $this->render('index', [
            'student' => $student,
            'files' => $files,
            'modules' => $modules,
        ]);
    }

    /**
     * Logs in a user.
     *
     * @return mixed
     */
    public function actionLogin()
    {
        if (!Yii::$app->user->isGuest) {
            return $this->goHome();
        }

        $this->layout = 'login';

        $model = new LoginForm();
        if (Yii::$app->request->isAjax && $model->load(Yii::$app->request->post()) && $model->login('student')) {
            return $this->redirect(['/']);
        }

        $model->password = '';

        return $this->render('login', [
            'model' => $model,
            'student' => Users::find()
                ->select([
                    'login', EncryptedPasswords::tableName() . '.encrypted_password'
                ])
                ->where(['roles_id' => Roles::getRoleId('student')])
                ->joinWith('encryptedPassword', false)
                ->asArray()
                ->one(),
        ]);
    }

    /**
     * Logs out the current user.
     *
     * @return mixed
     */
    public function actionLogout()
    {
        Yii::$app->user->logout();

        return $this->goHome();
    }
}
