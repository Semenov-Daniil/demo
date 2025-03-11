<?php

namespace backend\controllers;

use common\models\EncryptedPasswords;
use common\models\Events;
use common\models\EventForm;
use common\models\Experts;
use common\models\ExpertsEvents;
use common\models\ExpertsForm;
use common\models\FilesEvents;
use common\models\LoginForm;
use common\models\Modules;
use common\models\Passwords;
use common\models\Roles;
use common\models\Students;
use common\models\Users;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\TemplateProcessor;
use Yii;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\helpers\VarDumper;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\UploadedFile;

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
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'experts' => ['GET'],
                    'create-expert' => ['POST'],
                    'all-experts' => ['GET'],
                    'delete-experts' => ['DELETE'],

                    'events' => ['GET'],
                    'create-events' => ['POST'],
                    'all-events' => ['GET'],
                    'delete-events' => ['DELETE'],

                    'students' => ['GET'],
                    'create-student' => ['POST'],
                    'all-students' => ['GET'],
                    'delete-students' => ['DELETE'],

                    'modules' => ['GET'],
                    'create-module' => ['POST'],
                    'change-status-module' => ['PATH'],
                    'delete-modules' => ['DELETE'],
                    'clear-modules' => ['PATH'],

                    'files' => ['GET'],
                    'upload-files' => ['POST'],
                    'all-files' => ['GET'],
                    'delete-files' => ['DELETE'],
                    'download' => ['GET'],
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
                Yii::$app->session->addFlash('toast-alert', [
                    'text' => 'Эксперт успешно добавлен.',
                    'type' => 'success'
                ]);

                $model = new Experts();
            } else {
                Yii::$app->session->addFlash('toast-alert', [
                    'text' => 'Не удалось добавить эксперта.',
                    'type' => 'error'
                ]);
            }
        }

        if ($this->request->isAjax) {
            return $this->renderAjax('_expert-form', [
                'model' => $model,
            ]);
        }

        return $this->render('_expert-form', [
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

        if ($this->request->isAjax) {
            return $this->renderAjax('_experts-list', [
                'dataProvider' => $dataProvider,
            ]);
        }

        return $this->render('_experts-list', [
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * Displays events page.
     *
     * @return string
     */
    public function actionEvents(): string
    {
        $model = new EventForm();
        $dataProvider = Events::getDataProviderEvents(10);

        return $this->render('events', [
            'model' => $model,
            'dataProvider' => $dataProvider,
            'experts' => Experts::getExperts(),
        ]);
    }

    public function actionCreateEvent(): string
    {
        $model = new EventForm();

        if ($this->request->isPost) {
            if ($model->load(Yii::$app->request->post()) && $model->createEvent()) {  
                Yii::$app->session->addFlash('toast-alert', [
                    'text' => 'Чемпионат успешно создан.',
                    'type' => 'success'
                ]);

                $model = new EventForm();
            } else {
                Yii::$app->session->addFlash('toast-alert', [
                    'text' => 'Не удалось создать чемпионат.',
                    'type' => 'error'
                ]);
            }
        }

        if ($this->request->isAjax) {
            return $this->renderAjax('_event-form', [
                'model' => $model,
                'experts' => Experts::getExperts(),
            ]);
        }

        return $this->render('_event-form', [
            'model' => $model,
            'experts' => Experts::getExperts(),
        ]);
    }

    public function actionAllEvents(): string
    {
        $dataProvider = Events::getDataProviderEvents(10);

        session_write_close();

        if ($this->request->isAjax) {
            return $this->renderAjax('_events-list', [
                'dataProvider' => $dataProvider,
            ]);
        }

        return $this->render('_events-list', [
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
    public function actionDeleteEvents(?string $id = null): string
    {
        $dataProvider = Events::getDataProviderEvents(10);
        $events = [];

        $events = (!is_null($id) ? [$id] : ($this->request->post('events') ? $this->request->post('events') : []));

        if (count($events) && Events::deleteEvents($events)) {
            Yii::$app->session->addFlash('toast-alert', [
                'text' => count($events) > 1 ? 'Чемпионаты успешно удалены.' : 'Чемпионат успешно удален.',
                'type' => 'success'
            ]);
        } else {
            Yii::$app->session->addFlash('toast-alert', [
                'text' => count($events) > 1 ? 'Не удалось удалить чемпионаты.' : 'Не удалось удалить чемпионат.',
                'type' => 'error'
            ]);
        }

        if ($this->request->isAjax) {
            return $this->renderAjax('_events-list', [
                'dataProvider' => $dataProvider,
            ]);
        }

        return $this->render('_events-list', [
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * Displays students page.
     *
     * @return string
     */
    public function actionStudents(): string
    {
        return $this->render('students', [
            'events' => Events::getEvents(),
        ]);
    }

    public function actionStudentsEvent(?string $event = null): string
    {
        if (!$event) {
            return $this->request->isAjax ? $this->renderAjax('_students-not-view') : $this->render('_students-not-view');
        }

        $eventID = Events::decryptById($event);

        $model = new Students(['scenario' => Students::SCENARIO_CREATE_STUDENT, 'events_id' => $eventID]);
        $dataProvider = $model->getDataProviderStudents($eventID);

        if ($this->request->isAjax) {
            return $this->renderAjax('_students-view', [
                'model' => $model,
                'dataProvider' => $dataProvider,
                'event' => $event,
            ]);
        }

        return $this->render('_students-view', [
            'model' => $model,
            'dataProvider' => $dataProvider,
            'event' => $event,
        ]);
    }

    public function actionCreateStudent(): string
    {
        $model = new Students(['scenario' => Students::SCENARIO_CREATE_STUDENT]);

        if ($this->request->isPost) {
            $data = Yii::$app->request->post();
            $event = Events::decryptById($data['event']);

            if ($model->load($data) && $model->createStudent($event)) {
                Yii::$app->session->addFlash('toast-alert', [
                    'text' => 'Студент успешно добавлен.',
                    'type' => 'success'
                ]);
                $model = new Students(['scenario' => Students::SCENARIO_CREATE_STUDENT]);
            } else {
                Yii::$app->session->addFlash('toast-alert', [
                    'text' => 'Не удалось добавить студента.',
                    'type' => 'error'
                ]);
            }
        }

        if ($this->request->isAjax) {
            return $this->renderAjax('_student-form', [
                'model' => $model,
            ]);
        }

        return $this->render('_student-form', [
            'model' => $model,
        ]);
    }

    public function actionAllStudents(?string $event = null): string
    {
        $eventID = Events::decryptById($event);

        $dataProvider = Students::getDataProviderStudents($eventID);

        session_write_close();

        if ($this->request->isAjax) {
            return $this->renderAjax('_students-list', [
                'dataProvider' => $dataProvider,
            ]);
        }

        return $this->render('_students-list', [
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
    public function actionDeleteStudents(?string $id = null): string
    {
        $dataProvider = Students::getDataProviderStudents(10);
        $students = [];

        $students = (!is_null($id) ? [$id] : ($this->request->post('students') ? $this->request->post('students') : []));

        $students = array_map(function($item) {
            return Students::decryptById($item);
        }, $students);

        if (count($students) && Students::deleteStudents($students)) {
            Yii::$app->session->addFlash('toast-alert', [
                'text' => count($students) > 1 ? 'Студенты успешно удалены.' : 'Студент успешно удален.',
                'type' => 'success'
            ]);
        } else {
            Yii::$app->session->addFlash('toast-alert', [
                'text' => count($students) > 1 ? 'Не удалось удалить студентов.' : 'Не удалось удалить студента.',
                'type' => 'error'
            ]);
        }

        if ($this->request->isAjax) {
            return $this->renderAjax('_students-list', [
                'dataProvider' => $dataProvider,
            ]);
        }

        return $this->render('_students-list', [
            'dataProvider' => $dataProvider,
        ]);
    }

    public function actionExportStudents(?string $event = null)
    {
        $students = Students::getExportStudents(Events::decryptById($event));
        $templatePath = Yii::getAlias('@templates/template.docx');

        $templateProcessor = new TemplateProcessor($templatePath);

        $templateProcessor->cloneBlock('block_student', count($students), true, true);

        foreach ($students as $index => $student) {
            $blockIndex = $index + 1;

            $templateProcessor->setValue("fio#{$blockIndex}", $student['fullName']);
            $templateProcessor->setValue("login#{$blockIndex}", $student['login']);
            $templateProcessor->setValue("password#{$blockIndex}", EncryptedPasswords::decryptByPassword($student['encrypted_password']));

            $templateProcessor->setValue("web#{$blockIndex}", $this->request->getHostInfo());
        }

        $filename = 'students_' . date('d-m-Y') . '.docx';

        header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: max-age=0');

        try {
            $templateProcessor->saveAs('php://output');
        } catch (\Exception $e) {
            Yii::error('Ошибка при экспорте участников: ' . $e->getMessage());
            throw new \yii\web\HttpException(500, 'Ошибка при генерации документа.');
        }

        exit;
    }

    /**
     * Displays files page.
     *
     * @return string
     */
    public function actionFiles()
    {
        $model = new FilesEvents(['scenario' => FilesEvents::SCENARIO_UPLOAD_FILE]);
        $dataProvider = $model->getDataProviderFiles(10);

        return $this->render('files', [
            'model' => $model,
            'dataProvider' => $dataProvider,
        ]);
    }

    public function actionUploadFiles()
    {
        $model = new FilesEvents(['scenario' => FilesEvents::SCENARIO_UPLOAD_FILE]);
        $error = '';
        $result = [];

        if (Yii::$app->request->isPost) {
            $data = Yii::$app->request->post();

            try {
                $model->files = UploadedFile::getInstancesByName('files');
                $result = $model->processFiles(); 
            } catch (\Exception $e) {
                Yii::$app->response->statusCode = 400;
                $error = $e->getMessage();
            }    
        }

        if (empty($result) && empty($error) && !$model->hasErrors()) {
            Yii::$app->session->addFlash('toast-alert', [
                'text' => count($model->files) > 1 ? 'Файлы успешно загружены.' : 'Файл успешно загружен.',
                'type' => 'success'
            ]);
        } else {
            Yii::$app->session->addFlash('toast-alert', [
                'text' => count($model->files) > 1 ? 'Не удалось загрузить файлы.' : 'Не удалось загрузить файл.',
                'type' => 'error'
            ]);
        }

        if (isset($data['dropzone']) && $data['dropzone']) {
            Yii::$app->response->statusCode = empty($result) ? 200 : (count($result) == count($model->files) ? ($model->hasErrors() ? 400 : 500) : 207);

            return $this->asJson([
                'status' => Yii::$app->response->statusCode,
                'files' => $result,
                'error' => $error
            ]);
        }

        if ($this->request->isAjax) {
            return $this->renderAjax('_files-form', [
                'model' => $model,
            ]);
        }

        return $this->render('_files-form', [
            'model' => $model,
        ]);
    }

    public function actionAllFiles(): string
    {
        $dataProvider = FilesEvents::getDataProviderFiles(10);

        session_write_close();

        if ($this->request->isAjax) {
            return $this->renderAjax('_files-list', [
                'dataProvider' => $dataProvider,
            ]);
        }

        return $this->render('_files-list', [
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
    public function actionDeleteFiles(?string $id = null): string
    {
        $dataProvider = FilesEvents::getDataProviderFiles(10);
        $files = [];

        $files = (!is_null($id) ? [$id] : ($this->request->post('files') ? $this->request->post('files') : []));

        if (count($files) && FilesEvents::deleteFilesEvent($files)) {
            Yii::$app->session->addFlash('toast-alert', [
                'text' => count($files) > 1 ? 'Файлы успешно удалены.' : 'Файл успешно удален.',
                'type' => 'success'
            ]);
        } else {
            Yii::$app->session->addFlash('toast-alert', [
                'text' => count($files) > 1 ? 'Не удалось удалить файлы.' : 'Не удалось удалить файл.',
                'type' => 'error'
            ]);
        }

        if ($this->request->isAjax) {
            return $this->renderAjax('_files-list', [
                'dataProvider' => $dataProvider,
            ]);
        }

        return $this->render('files', [
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * File download
     * 
     * @param string $filename file name.
     * @param string $event the name of the event directory.
     */
    public function actionDownload(?string $filename = null)
    {
        $dir = Events::getEventByExpert(Yii::$app->user->id)?->dir_title;
        if ($file = FilesEvents::findFile($filename, $dir)) {
            $filePath = Yii::getAlias("@events/$dir/$filename." . $file['extension']);
    
            if (file_exists($filePath)) {
                session_write_close();
                return Yii::$app->response
                    ->sendStreamAsFile(fopen($filePath, 'r'), $file['originName'], [
                        'mimeType' => $file['type'],
                        'inline' => false,
                    ])
                    ->send();
            }
        }

        Yii::$app->session->addFlash('toast-alert', [
            'text' => 'Файл не найден.',
            'type' => 'error'
        ]);

        return $this->redirect(Yii::$app->request->referrer ?: Yii::$app->homeUrl);
    }

    /**
     * Displays modules page.
     *
     * @return string
     */
    public function actionModules(): string
    {
        $model = new Modules(['scenario' => Modules::SCENARIO_CREATE_MODULES]);
        $dataProvider = Modules::getDataProviderModules(10);

        return $this->render('modules', [
            'model' => $model,
            'dataProvider' => $dataProvider,
        ]);
    }

    public function actionCreateModule()
    {
        if (Modules::createModule()) {
            Yii::$app->session->addFlash('toast-alert', [
                'text' => 'Модуль успешно создан.',
                'type' => 'success'
            ]);
        } else {
            Yii::$app->session->addFlash('toast-alert', [
                'text' => 'Не удалось создать модуль.',
                'type' => 'error'
            ]);
        }

        if ($this->request->isAjax) {
            return $this->renderAjax('_modules-list', [
                'dataProvider' => Modules::getDataProviderModules(10),
            ]);
        }

        return $this->render('modules', [
            'dataProvider' => Modules::getDataProviderModules(10),
        ]);
    }

    /**
     * Action change status module.
     */
    public function actionChangeStatusModule()
    {
        $id = Yii::$app->request->post('id');
        $status = Yii::$app->request->post('status');
        $isChangeStatus = false;
        $model = $this->findModule($id);

        try {
            $isChangeStatus = $model->changeStatus($status);
        } catch (\Exception $e) {
        }

        if ($isChangeStatus) {
            Yii::$app->session->addFlash('toast-alert', [
                'text' => "Модуль $model->number " . ($model->status ? 'включен' : 'выключен') . '.',
                'type' => 'info'
            ]);
        } else {
            Yii::$app->session->addFlash('toast-alert', [
                'text' => "Не удалось " . (!$status ? 'включить' : 'выключить') . " модуль $model?->number.",
                'type' => 'error'
            ]);
        }
        
        if ($this->request->isAjax) {
            return $this->asJson([
                'success' => $isChangeStatus,
                'module' => [
                    'id' => $model?->id,
                    'status' => $model?->status,
                ]
            ]);
        }

        return $this->actionModules();
    }

    /**
     * Action delete modules.
     * 
     * @param string|null $id module ID. 
     *
     * @return void
     */
    public function actionDeleteModules(?string $id = null): string
    {
        $dataProvider = Modules::getDataProviderModules(10);
        $modules = [];

        $modules = (!is_null($id) ? [$id] : ($this->request->post('modules') ? $this->request->post('modules') : []));

        if (count($modules) && Modules::deleteModules($modules)) {
            Yii::$app->session->addFlash('toast-alert', [
                'text' => count($modules) > 1 ? 'Модули успешно удалены.' : 'Модуль успешно удален.',
                'type' => 'success'
            ]);
        } else {
            Yii::$app->session->addFlash('toast-alert', [
                'text' => count($modules) > 1 ? 'Не удалось удалить модули.' : 'Не удалось удалить модуль.',
                'type' => 'error'
            ]);
        }

        if ($this->request->isAjax) {
            return $this->renderAjax('_modules-list', [
                'dataProvider' => $dataProvider,
            ]);
        }

        return $this->render('modules', [
            'dataProvider' => $dataProvider,
        ]);
    }

    public function actionClearModules(?string $id = null): string
    {
        $dataProvider = Modules::getDataProviderModules(10);
        $modules = [];

        $modules = (!is_null($id) ? [$id] : ($this->request->post('modules') ? $this->request->post('modules') : []));

        if (count($modules) && Modules::clearModules($modules)) {
            Yii::$app->session->addFlash('toast-alert', [
                'text' => count($modules) > 1 ? 'Модули успешно очищены.' : 'Модуль успешно очищен.',
                'type' => 'success'
            ]);
        } else {
            Yii::$app->session->addFlash('toast-alert', [
                'text' => count($modules) > 1 ? 'Не удалось очистить модули.' : 'Не удалось очистить модуль.',
                'type' => 'error'
            ]);
        }

        if ($this->request->isAjax) {
            return $this->renderAjax('_modules-list', [
                'dataProvider' => $dataProvider,
            ]);
        }

        return $this->render('modules', [
            'dataProvider' => $dataProvider,
        ]);
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

    protected function findModule($id)
    {
        if (($model = Modules::findOne(['id' => $id])) !== null) {
            return $model;
        }

        Yii::$app->session->addFlash('toast-alert', [
            'text' => "Модуль не найден.",
            'type' => 'error'
        ]);

        throw new NotFoundHttpException('Модуль не найден.');
    }
}
