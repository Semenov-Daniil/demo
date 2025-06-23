<?php

namespace frontend\controllers;

use common\models\EncryptedPasswords;
use common\models\Events;
use common\models\Files;
use common\models\LoginForm;
use common\models\Modules;
use common\models\Roles;
use common\models\Students;
use common\models\Users;
use common\services\FileService;
use common\services\ModuleService;
use Yii;
use yii\filters\AccessControl;
use yii\helpers\VarDumper;
use yii\web\Controller;
use yii\web\NotFoundHttpException;

/**
 * Main controller
 */
class MainController extends Controller
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
                        'actions' => ['login', 'error'],
                        'allow' => true,
                        'roles' => ['?'],
                    ],
                    [
                        'allow' => true,
                        'roles' => ['student'],
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

    /**
     * Displays homepage.
     *
     * @return mixed
     */
    public function actionIndex()
    {
        $student = $this->findStudent(Yii::$app->user->id);
        $files = Files::getDataProviderFilesStudent($student->events_id, 'index');
        $modules = Modules::getModulesStudent($student);

        return $this->render('index', [
            'student' => $student,
            'files' => $files,
            'modules' => $modules,
        ]);
    }

    public function actionFilesList()
    {
        $student = $this->findStudent(Yii::$app->user->id);
        return $this->renderAjax('_files-list', [ 'files' => Files::getDataProviderFilesStudent($student->events_id, 'index') ]);
    }

    public function actionSseFilesUpdates()
    {
        $student = $this->findStudent(Yii::$app->user->id);
        Yii::$app->sse->subscriber((new FileService)->getEventChannel($student->events_id));
    }

    public function actionModulesList()
    {
        $student = $this->findStudent(Yii::$app->user->id);
        return $this->renderAjax('_modules-list', [ 'modules' => Modules::getModulesStudent($student) ]);
    }

    public function actionSseModulesUpdates()
    {
        $student = $this->findStudent(Yii::$app->user->id);
        Yii::$app->sse->subscriber((new ModuleService)->getEventChannel($student->events_id));
    }

    /**
     * File download
     * 
     * @param string $filename file name.
     */
    public function actionDownload(string|int $id)
    {
        $model = $this->findFile($id);
        $path = (new FileService())->getFilePath($model);

        if (file_exists($path)) {
            $pathArray = explode('/', $path);
            return Yii::$app->response
                ->sendStreamAsFile(fopen($path, 'r'), end($pathArray), [
                    'mimeType' => mime_content_type($path),
                    'inline' => false,
                ])
                ->send();
        }

        $this->addToastMessage('Файл не найден.', 'error');

        return $this->redirect(Yii::$app->request->referrer ?: Yii::$app->homeUrl);
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
            'model' => $model
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

    protected function findFile(int|string $id): Files
    {
        if ($id && ($model = Files::findOne(['id' => $id])) !== null) {
            return $model;
        }

        Yii::$app->toast->addToast('Файл не найден.', 'error');

        throw new NotFoundHttpException('Файл не найден.');
    }

    protected function findStudent(int|null $id = null)
    {
        if ($id && $student = Students::findOne(['students_id' => $id])) {
            return $student;
        }

        Yii::$app->toast->addToast('Студент не найден.', 'error');

        throw new NotFoundHttpException('Студент не найден.');
    }
}
