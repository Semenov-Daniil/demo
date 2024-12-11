<?php

namespace app\controllers;

use app\models\FilesCompetencies;
use app\models\FilesEvents;
use app\models\LoginForm;
use app\models\Passwords;
use app\models\Roles;
use app\models\Users;
use app\models\UsersCompetencies;
use Yii;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\web\Controller;

class SiteController extends Controller
{
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    [
                        'allow' => true,
                        'actions' => ['login'],
                        'roles' => ['?'],
                    ],
                    [
                        'actions' => ['index', 'logout', 'download'],
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
                'denyCallback' => function ($rule, $action) {
                    Yii::$app->user->isGuest ? $this->redirect(['login']) : $this->redirect(['/']);
                }
            ],
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'logout' => ['post'],
                    'download' => ['get'],
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
                'class' => 'yii\web\ErrorAction',
            ],
            'captcha' => [
                'class' => 'yii\captcha\CaptchaAction',
                'fixedVerifyCode' => YII_ENV_TEST ? 'testme' : null,
            ],
        ];
    }

    public function actionIndex()
    {
        return $this->redirect([(Yii::$app->user->can('expert') ? '/expert' : '/account')]);
    }

    /**
     * Login user
     */
    public function actionLogin()
    {
        $this->layout = 'login';

        $model = new LoginForm();

        if (Yii::$app->request->isAjax && $model->load(Yii::$app->request->post()) && $model->login()) {
           $this->redirect(['/'])->send();
        }

        $model->password = '';
        return $this->render('login', [
            'model' => $model,
            'users' => [
                'expert' => Users::find()
                    ->select(['login', Passwords::tableName() . '.password'])
                    ->where(['roles_id' => Roles::getRoleId('expert')])
                    ->joinWith('openPassword', false)
                    ->one(),
                'student' => Users::find()
                    ->select(['login', Passwords::tableName() . '.password'])
                    ->where(['roles_id' => Roles::getRoleId('student')])
                    ->joinWith('openPassword', false)
                    ->one(),
            ]
        ]);
    }

    public function actionLogout()
    {
        if  (Yii::$app->request->isPost) {
            Yii::$app->user->logout();
        }
        return $this->goHome();
    }

    /**
     * File download
     * 
     * @param string $filename file name.
     * @param string $event the name of the event directory.
     */
    public function actionDownload(string $event, string $filename)
    {
        if ($file = FilesEvents::findFile($filename, $event)) {
            $filePath = Yii::getAlias('@events') . "/$event/$filename." . $file['extension'];
    
            if (file_exists($filePath)) {
                return Yii::$app->response
                    ->sendStreamAsFile(fopen($filePath, 'r'), $file['originName'], [
                        'mimeType' => $file['type'],
                    ])
                    ->send();
            }
        }

        return $this->redirect(Yii::$app->request->referrer ?: Yii::$app->homeUrl);
    }
}
