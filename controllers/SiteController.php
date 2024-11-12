<?php

namespace app\controllers;

use app\models\FilesCompetencies;
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
        return $this->goHome();
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
                    ->joinWith('passwords', false)
                    ->one(),
                'student' => Users::find()
                    ->select(['login', Passwords::tableName() . '.password'])
                    ->where(['roles_id' => Roles::getRoleId('student')])
                    ->joinWith('passwords', false)
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
     * @param string $competence the name of the competence directory.
     */
    public function actionDownload(string $competence, string $filename)
    {
        if ($file = FilesCompetencies::findFile($filename, $competence)) {
            $filePath = Yii::getAlias('@competencies') . "/$competence/$filename." . $file['extension'];
    
            if (file_exists($filePath)) {
                return Yii::$app->response
                    ->sendStreamAsFile(fopen($filePath, 'r'), $file['originFullName'], [
                        'mimeType' => $file['type'],
                    ])
                    ->send();
            }
        }

        return $this->redirect(Yii::$app->request->referrer ?: Yii::$app->homeUrl);
    }
}
