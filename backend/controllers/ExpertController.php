<?php

namespace backend\controllers;

use common\models\EncryptedPasswords;
use common\models\ExpertsEvents;
use common\models\FilesEvents;
use common\models\LoginForm;
use common\models\Modules;
use common\models\Passwords;
use common\models\Roles;
use common\models\StudentsEvents;
use common\models\Users;
use Yii;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\web\Controller;
use yii\web\UploadedFile;

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
                        'actions' => ['login', 'error'],
                        'allow' => true,
                        'roles' => ['?'],
                    ],
                    [
                        //'actions' => ['logout', 'experts', 'delete-experts', 'students', 'delete-students', 'modules', 'change-status-modules', 'delete-modules'],
                        'allow' => true,
                        'roles' => ['expert'],
                    ],
                ],
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

    /**
     * Displays experts page.
     *
     * @return string
     */
    public function actionExperts(): string
    {
        $model = new ExpertsEvents();
        $dataProvider = $model->getDataProviderExperts(10);

        if (Yii::$app->request->isPost) {
            if ($model->load(Yii::$app->request->post()) && $model->addExpert()) {  
                Yii::$app->session->addFlash('toast-alert', [
                    'text' => 'Эксперт успешно добавлен.',
                    'type' => 'success'
                ]);

                $model = new ExpertsEvents();
            } else {
                Yii::$app->session->addFlash('toast-alert', [
                    'text' => 'Не удалось добавить эксперта.',
                    'type' => 'error'
                ]);
            }
        }

        return $this->render('experts', [
            'model' => $model,
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * Action delete experts.
     *
     * @param string $id expert ID. 
     * 
     * @return void
     */
    public function actionDeleteExperts(): void
    {
        $experts = [];

        if ($this->request->get('id')) {
            $experts[] = $this->request->get('id');
        }

        if ($this->request->post('selection')) {
            $experts = array_unique(array_merge($experts, $this->request->post('selection')));
        }

        if (count($experts) && ExpertsEvents::deleteExperts($experts)) {
            Yii::$app->session->addFlash('toast-alert', [
                'text' => count($experts) > 1 ? 'Эксперты успешно удалены.' : 'Эксперт успешно удален.',
                'type' => 'success'
            ]);
        } else {
            Yii::$app->session->addFlash('toast-alert', [
                'text' => count($experts) > 1 ? 'Не удалось удалить экспертов.' : 'Не удалось удалить эксперта.',
                'type' => 'error'
            ]);
        }
    }

    /**
     * Displays students page.
     *
     * @return string
     */
    public function actionStudents(): string
    {
        $model = new StudentsEvents(['scenario' => StudentsEvents::SCENARIO_ADD_STUDENT]);
        $dataProvider = $model->getDataProviderStudents(20);

        if (Yii::$app->request->isPost) {
            if ($model->load(Yii::$app->request->post()) && $model->addStudent()) {
                Yii::$app->session->addFlash('toast-alert', [
                    'text' => 'Студент успешно добавлен.',
                    'type' => 'success'
                ]);
                $model = new StudentsEvents(['scenario' => StudentsEvents::SCENARIO_ADD_STUDENT]);
            } else {
                Yii::$app->session->addFlash('toast-alert', [
                    'text' => 'Не удалось добавить студента.',
                    'type' => 'error'
                ]);
            }
        }

        return $this->render('students', [
            'model' => $model,
            'dataProvider' => $dataProvider,
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
            if (StudentsEvents::deleteStudent($id)) {
                Yii::$app->session->setFlash('info', 'Студент успешно удален.');
            } else {
                Yii::$app->session->setFlash('error', 'Не удалось удалить студента.');
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
        $model = new FilesEvents(['scenario' => FilesEvents::SCENARIO_UPLOAD_FILE]);
        // $dataProvider = $model->getDataProviderFiles(20);

        if (Yii::$app->request->isGet && Yii::$app->request->isPjax) {
            $get = Yii::$app->request->get();

            switch ((isset($get['_pjax']) ? $get['_pjax'] : '')) {
                case '#pjax-upload-file':
                    $result = $this->renderAjax('_files-form', [
                        'model' => $model,
                    ]);
                    break;
                case '#pjax-files':
                    $result = $this->renderAjax('_files-list', [
                        'dataProvider' => $model->getDataProviderFiles(20),
                    ]); 
                    break;
                default:
                    $result = $this->renderAjax('files', [
                        'model' => $model,
                        'dataProvider' => $model->getDataProviderFiles(20),
                    ]);
            }

            return $result;
        }

        if (Yii::$app->request->isPost) {
            $data = Yii::$app->request->post();
            $error = '';

            try {
                $model->files = UploadedFile::getInstancesByName('files');

                $result = $model->processFiles();  

            } catch (\Exception $e) {
                var_dump($e);die;
                Yii::$app->response->statusCode = 422;
                $error = $e->getMessage();
            }   
            
            if (isset($data['dropzone']) && $data['dropzone']) {
                return $this->asJson([
                    'files' => $result,
                    'error' => $error
                ]);
            }

            return $this->renderAjax('_files-form', [
                'model' => $model,
            ]);
        }

        return $this->render('files', [
            'model' => $model,
            'dataProvider' => $model->getDataProviderFiles(20),
        ]);
    }

    /**
     * Action delete students.
     * 
     * @param string|null $id student ID. 
     *
     * @return void
     */
    public function actionDeleteFiles(string|null $id = null): void
    {
        if (Yii::$app->request->isAjax) {
            if (FilesEvents::deleteFileEvent($id)) {
                Yii::$app->session->setFlash('success', 'Файл успешно удален.');
            } else {
                Yii::$app->session->setFlash('error', 'Не удалось удалить файл.');
            }
        }
    }

    /**
     * File download
     * 
     * @param string $filename file name.
     * @param string $event the name of the event directory.
     */
    public function actionDownload(string $dir, string $filename)
    {
        if ($file = FilesEvents::findFile($filename, $dir)) {
            $filePath = Yii::getAlias("@events/$dir/$filename." . $file['extension']);
    
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
     */
    public function actionChangeStatusModules()
    {
        if (Yii::$app->request->isAjax) {
            $model = new Modules();
            if ($model->load(Yii::$app->request->post(), '')) {
                if ($model->changeStatus()) {
                    Yii::$app->response->statusCode = 200;
                    return $this->asJson(['status' => $model->status]);
                } else {
                    Yii::$app->response->statusCode = 500;
                    return $this->asJson(['status' => !$model->status]);
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
                Yii::$app->session->setFlash('success', 'Модуль успешно удален.');
            } else {
                Yii::$app->session->setFlash('error', 'Не удалось удалить модуль.');
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
