<?php

namespace backend\controllers;

use common\models\EncryptedPasswords;
use common\models\LoginForm;
use common\models\Roles;
use common\models\Users;
use Yii;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\web\NotFoundHttpException;

/**
 * Main controller
 */
class MainController extends Controller
{
    private $controllers = [
        'expert' => 'backend\controllers\ExpertController',
        'event' => 'backend\controllers\EventController',
        'student' => 'backend\controllers\StudentController',
        'module' => 'backend\controllers\ModuleController',
        'file' => 'backend\controllers\FileController',
        'participant' => 'backend\controllers\ParticipantController',
    ];
    
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
                        'actions' => ['login', 'error'],
                        'allow' => true,
                        'roles' => ['?'],
                    ],
                    [
                        'allow' => true,
                        'roles' => ['expert'],
                    ],
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
        ];
    }

    public function actionDispatch($action)
    {
        $actionCamelCase = $this->convertToCamelCase($action);
        $actionMethod = 'action' . $actionCamelCase;

        foreach ($this->controllers as $controllerId => $controllerClass) {
            if (class_exists($controllerClass)) {
                $controller = new $controllerClass($controllerId, $this->module);
                if (method_exists($controller, $actionMethod)) {
                    return $controller->runAction($action, Yii::$app->request->get());
                }
            }
        }

        throw new NotFoundHttpException("Action '$action' not found.");
    }

    private function convertToCamelCase($input)
    {
        return str_replace('-', '', ucwords($input, '-'));
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
        if (Yii::$app->request->isAjax && $model->load(Yii::$app->request->post()) && $model->login('expert')) {
            return $this->redirect(['/']);
        }

        $model->password = '';

        return $this->render('login', [
            'model' => $model,
            'expert' => Users::find()
                ->select([
                    'login', EncryptedPasswords::tableName() . '.encrypted_password'
                ])
                ->where(['roles_id' => Roles::getRoleId('expert')])
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
