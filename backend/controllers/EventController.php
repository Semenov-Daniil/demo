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
use yii\web\Response;
use yii\web\UploadedFile;

class EventController extends Controller
{
    public $defaultAction = 'events';

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
                    'events' => ['GET'],
                    'create-event' => ['POST'],
                    'all-events' => ['GET'],
                    'update-event' => ['GET', 'PATCH'],
                    'delete-events' => ['DELETE'],
                ],
            ],
        ];
    }

    /**
     * Displays events page.
     *
     * @return string
     */
    public function actionEvents(): string
    {
        $model = new EventForm();
        $dataProvider = Events::getDataProviderEvents(Yii::$app->user->id);

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
                Yii::$app->session->addFlash('toastify', [
                    'text' => 'Чемпионат успешно создан.',
                    'type' => 'success'
                ]);

                $model = new EventForm();
            } else {
                Yii::$app->session->addFlash('toastify', [
                    'text' => 'Не удалось создать чемпионат.',
                    'type' => 'error'
                ]);
            }
        }

        if ($this->request->isAjax) {
            return $this->renderAjax('_event-create', [
                'model' => $model,
                'experts' => Experts::getExperts(),
            ]);
        }

        return $this->render('_event-create', [
            'model' => $model,
            'experts' => Experts::getExperts(),
        ]);
    }

    public function actionAllEvents(): string
    {
        $dataProvider = Events::getDataProviderEvents(Yii::$app->user->id);

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

    public function actionUpdateEvent(?string $id = null): Response|string
    {
        $model = $this->findEvent($id);
        $model->scenario = Events::SCENARIO_UPDATE;

        if ($this->request->isPatch) {
            if ($model->load($this->request->post()) && $model->save()) {
                Yii::$app->session->addFlash('toastify', [
                    'text' => 'Чемпионат успешно обновлен.',
                    'type' => 'success'
                ]);

                return $this->asJson([
                    'success' => true
                ]);
            } else {
                Yii::$app->session->addFlash('toastify', [
                    'text' => 'Не удалось обновить чемпионат.',
                    'type' => 'error'
                ]);
            }
        }

        if ($this->request->isAjax) {
            return $this->renderAjax('_event-update', [
                'model' => $model,
                'experts' => Experts::getExperts(),
            ]);
        }

        return $this->render('_event-update', [
            'model' => $model,
            'experts' => Experts::getExperts(),
        ]);
    }

    /**
     * Action delete events.
     *
     * @param string $id expert ID. 
     * 
     * @return void
     */
    public function actionDeleteEvents(?string $id = null): string
    {
        $dataProvider = Events::getDataProviderEvents(Yii::$app->user->id);
        $events = [];

        $events = (!is_null($id) ? [$id] : ($this->request->post('events') ? $this->request->post('events') : []));

        if (count($events) && Events::deleteEvents($events)) {
            Yii::$app->session->addFlash('toastify', [
                'text' => count($events) > 1 ? 'Чемпионаты успешно удалены.' : 'Чемпионат успешно удален.',
                'type' => 'success'
            ]);
        } else {
            Yii::$app->session->addFlash('toastify', [
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

    // /**
    //  * Displays students page.
    //  *
    //  * @return string
    //  */
    // public function actionStudents(): string
    // {
    //     return $this->render('students/students', [
    //         'events' => Yii::$app->user->can('sExpert') ? Events::getExpertEvents() : Events::getEvents(Yii::$app->user->id),
    //     ]);
    // }

    // public function actionStudentsEvent(?string $event = null): string
    // {
    //     if (!$event) {
    //         return $this->request->isAjax ? $this->renderAjax('_students-not-view') : $this->render('_students-not-view');
    //     }

    //     $model = new Students(['scenario' => Students::SCENARIO_CREATE_STUDENT, 'events_id' => $event]);
    //     $dataProvider = $model->getDataProviderStudents($event);

    //     if ($this->request->isAjax) {
    //         return $this->renderAjax('students/_students-view', [
    //             'model' => $model,
    //             'dataProvider' => $dataProvider,
    //             'event' => $event,
    //         ]);
    //     }

    //     return $this->render('students/_students-view', [
    //         'model' => $model,
    //         'dataProvider' => $dataProvider,
    //         'event' => $event,
    //     ]);
    // }

    // public function actionCreateStudent(): string
    // {
    //     $model = new Students(['scenario' => Students::SCENARIO_CREATE_STUDENT]);

    //     if ($this->request->isPost) {
    //         $data = Yii::$app->request->post();
    //         $event = $data['event'];

    //         if ($model->load($data) && $model->createStudent($event)) {
    //             Yii::$app->session->addFlash('toastify', [
    //                 'text' => 'Студент успешно добавлен.',
    //                 'type' => 'success'
    //             ]);
    //             $model = new Students(['scenario' => Students::SCENARIO_CREATE_STUDENT]);
    //         } else {
    //             Yii::$app->session->addFlash('toastify', [
    //                 'text' => 'Не удалось добавить студента.',
    //                 'type' => 'error'
    //             ]);
    //         }
    //     }

    //     if ($this->request->isAjax) {
    //         return $this->renderAjax('students/_student-form', [
    //             'model' => $model,
    //         ]);
    //     }

    //     return $this->render('students/_student-form', [
    //         'model' => $model,
    //     ]);
    // }

    // public function actionAllStudents(?string $event = null): string
    // {
    //     $dataProvider = Students::getDataProviderStudents($event);

    //     session_write_close();

    //     if ($this->request->isAjax) {
    //         return $this->renderAjax('students/_students-list', [
    //             'dataProvider' => $dataProvider,
    //             'event' => $event
    //         ]);
    //     }

    //     return $this->render('students/_students-list', [
    //         'dataProvider' => $dataProvider,
    //         'event' => $event
    //     ]);
    // }

    // /**
    //  * Action delete students.
    //  * 
    //  * @param string|null $id student ID. 
    //  *
    //  * @return void
    //  */
    // public function actionDeleteStudents(?string $id = null): string
    // {
    //     $dataProvider = Students::getDataProviderStudents(10);
    //     $students = [];

    //     $students = (!is_null($id) ? [$id] : ($this->request->post('students') ? $this->request->post('students') : []));

    //     if (count($students) && Students::deleteStudents($students)) {
    //         Yii::$app->session->addFlash('toastify', [
    //             'text' => count($students) > 1 ? 'Студенты успешно удалены.' : 'Студент успешно удален.',
    //             'type' => 'success'
    //         ]);
    //     } else {
    //         Yii::$app->session->addFlash('toastify', [
    //             'text' => count($students) > 1 ? 'Не удалось удалить студентов.' : 'Не удалось удалить студента.',
    //             'type' => 'error'
    //         ]);
    //     }

    //     if ($this->request->isAjax) {
    //         return $this->renderAjax('students/_students-list', [
    //             'dataProvider' => $dataProvider,
    //         ]);
    //     }

    //     return $this->render('students/_students-list', [
    //         'dataProvider' => $dataProvider,
    //     ]);
    // }

    // public function actionExportStudents(?string $event = null)
    // {
    //     $students = Students::getExportStudents($event);
    //     $templatePath = Yii::getAlias('@templates/template.docx');

    //     $templateProcessor = new TemplateProcessor($templatePath);

    //     $templateProcessor->cloneBlock('block_student', count($students), true, true);

    //     foreach ($students as $index => $student) {
    //         $blockIndex = $index + 1;

    //         $templateProcessor->setValue("fio#{$blockIndex}", $student['fullName']);
    //         $templateProcessor->setValue("login#{$blockIndex}", $student['login']);
    //         $templateProcessor->setValue("password#{$blockIndex}", EncryptedPasswords::decryptByPassword($student['encrypted_password']));

    //         $templateProcessor->setValue("web#{$blockIndex}", $this->request->getHostInfo());
    //     }

    //     $filename = 'students_' . date('d-m-Y') . '.docx';

    //     header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
    //     header('Content-Disposition: attachment; filename="' . $filename . '"');
    //     header('Cache-Control: max-age=0');

    //     try {
    //         $templateProcessor->saveAs('php://output');
    //     } catch (\Exception $e) {
    //         Yii::error('Ошибка при экспорте участников: ' . $e->getMessage());
    //         throw new \yii\web\HttpException(500, 'Ошибка при генерации документа.');
    //     }

    //     exit;
    // }

    // /**
    //  * Displays files page.
    //  *
    //  * @return string
    //  */
    // public function actionFiles()
    // {
    //     $model = new FilesEvents(['scenario' => FilesEvents::SCENARIO_UPLOAD_FILE]);
    //     $dataProvider = $model->getDataProviderFiles(Yii::$app->user->identity->event->id);

    //     return $this->render('files/files', [
    //         'model' => $model,
    //         'dataProvider' => $dataProvider,
    //         'events' => Yii::$app->user->can('sExpert') ? Events::getExpertEvents() : Events::getEvents(Yii::$app->user->id),
    //     ]);
    // }

    // public function actionUploadFiles()
    // {
    //     $model = new FilesEvents(['scenario' => FilesEvents::SCENARIO_UPLOAD_FILE]);
    //     $error = '';
    //     $result = [];

    //     if (Yii::$app->request->isPost) {
    //         $data = Yii::$app->request->post();

    //         try {
    //             $model->files = UploadedFile::getInstancesByName('files');
    //             $result = $model->processFiles(Yii::$app->user->identity->event->id); 
    //         } catch (\Exception $e) {
    //             Yii::$app->response->statusCode = 400;
    //             $error = $e->getMessage();
    //         }    
    //     }

    //     if (empty($result) && empty($error) && !$model->hasErrors()) {
    //         Yii::$app->session->addFlash('toastify', [
    //             'text' => count($model->files) > 1 ? 'Файлы успешно загружены.' : 'Файл успешно загружен.',
    //             'type' => 'success'
    //         ]);
    //     } else {
    //         Yii::$app->session->addFlash('toastify', [
    //             'text' => count($model->files) > 1 ? 'Не удалось загрузить файлы.' : 'Не удалось загрузить файл.',
    //             'type' => 'error'
    //         ]);
    //     }

    //     if (isset($data['dropzone']) && $data['dropzone']) {
    //         Yii::$app->response->statusCode = empty($result) ? 200 : (count($result) == count($model->files) ? ($model->hasErrors() ? 400 : 500) : 207);

    //         return $this->asJson([
    //             'status' => Yii::$app->response->statusCode,
    //             'files' => $result,
    //             'error' => $error
    //         ]);
    //     }

    //     if ($this->request->isAjax) {
    //         return $this->renderAjax('files/_files-form', [
    //             'model' => $model,
    //         ]);
    //     }

    //     return $this->render('files/_files-form', [
    //         'model' => $model,
    //     ]);
    // }

    // public function actionAllFiles(): string
    // {
    //     $dataProvider = FilesEvents::getDataProviderFiles(Yii::$app->user->identity->event->id);

    //     session_write_close();

    //     if ($this->request->isAjax) {
    //         return $this->renderAjax('files/_files-list', [
    //             'dataProvider' => $dataProvider,
    //         ]);
    //     }

    //     return $this->render('files/_files-list', [
    //         'dataProvider' => $dataProvider,
    //     ]);
    // }

    // /**
    //  * Action delete students.
    //  * 
    //  * @param string|null $id student ID. 
    //  *
    //  * @return void
    //  */
    // public function actionDeleteFiles(?string $id = null): string
    // {
    //     $dataProvider = FilesEvents::getDataProviderFiles(Yii::$app->user->identity->event->id);
    //     $files = [];

    //     $files = (!is_null($id) ? [$id] : ($this->request->post('files') ? $this->request->post('files') : []));

    //     if (count($files) && FilesEvents::deleteFilesEvent($files)) {
    //         Yii::$app->session->addFlash('toastify', [
    //             'text' => count($files) > 1 ? 'Файлы успешно удалены.' : 'Файл успешно удален.',
    //             'type' => 'success'
    //         ]);
    //     } else {
    //         Yii::$app->session->addFlash('toastify', [
    //             'text' => count($files) > 1 ? 'Не удалось удалить файлы.' : 'Не удалось удалить файл.',
    //             'type' => 'error'
    //         ]);
    //     }

    //     if ($this->request->isAjax) {
    //         return $this->renderAjax('files/_files-list', [
    //             'dataProvider' => $dataProvider,
    //         ]);
    //     }

    //     return $this->render('files/_files-list', [
    //         'dataProvider' => $dataProvider,
    //     ]);
    // }

    // /**
    //  * File download
    //  * 
    //  * @param string $filename file name.
    //  * @param string $event the name of the event directory.
    //  */
    // public function actionDownload(?string $filename = null)
    // {
    //     $dir = Events::getEventByExpert(Yii::$app->user->id)?->dir_title;
    //     if ($file = FilesEvents::findFile($filename, $dir)) {
    //         $filePath = Yii::getAlias("@events/$dir/$filename." . $file['extension']);
    
    //         if (file_exists($filePath)) {
    //             session_write_close();
    //             return Yii::$app->response
    //                 ->sendStreamAsFile(fopen($filePath, 'r'), $file['originName'], [
    //                     'mimeType' => $file['type'],
    //                     'inline' => false,
    //                 ])
    //                 ->send();
    //         }
    //     }

    //     Yii::$app->session->addFlash('toastify', [
    //         'text' => 'Файл не найден.',
    //         'type' => 'error'
    //     ]);

    //     return $this->redirect(Yii::$app->request->referrer ?: Yii::$app->homeUrl);
    // }

    // /**
    //  * Displays modules page.
    //  *
    //  * @return string
    //  */
    // public function actionModules(): string
    // {
    //     $model = new Modules(['scenario' => Modules::SCENARIO_CREATE_MODULES]);
    //     $dataProvider = Modules::getDataProviderModules(10);

    //     return $this->render('modules/modules', [
    //         'model' => $model,
    //         'dataProvider' => $dataProvider,
    //         'events' => Yii::$app->user->can('sExpert') ? Events::getExpertEvents() : Events::getEvents(Yii::$app->user->id),
    //     ]);
    // }

    // public function actionCreateModule()
    // {
    //     if (Modules::createModule()) {
    //         Yii::$app->session->addFlash('toastify', [
    //             'text' => 'Модуль успешно создан.',
    //             'type' => 'success'
    //         ]);
    //     } else {
    //         Yii::$app->session->addFlash('toastify', [
    //             'text' => 'Не удалось создать модуль.',
    //             'type' => 'error'
    //         ]);
    //     }

    //     if ($this->request->isAjax) {
    //         return $this->renderAjax('modules/_modules-list', [
    //             'dataProvider' => Modules::getDataProviderModules(10),
    //         ]);
    //     }

    //     return $this->render('modules/_modules-list', [
    //         'dataProvider' => Modules::getDataProviderModules(10),
    //     ]);
    // }

    // /**
    //  * Action change status module.
    //  */
    // public function actionChangeStatusModule()
    // {
    //     $id = Yii::$app->request->post('id');
    //     $status = Yii::$app->request->post('status');
    //     $isChangeStatus = false;
    //     $model = $this->findModule($id);

    //     try {
    //         $isChangeStatus = $model->changeStatus($status);
    //     } catch (\Exception $e) {
    //     }

    //     if ($isChangeStatus) {
    //         Yii::$app->session->addFlash('toastify', [
    //             'text' => "Модуль $model->number " . ($model->status ? 'включен' : 'выключен') . '.',
    //             'type' => 'info'
    //         ]);
    //     } else {
    //         Yii::$app->session->addFlash('toastify', [
    //             'text' => "Не удалось " . (!$status ? 'включить' : 'выключить') . " модуль $model?->number.",
    //             'type' => 'error'
    //         ]);
    //     }
        
    //     if ($this->request->isAjax) {
    //         return $this->asJson([
    //             'success' => $isChangeStatus,
    //             'module' => [
    //                 'id' => $model?->id,
    //                 'status' => $model?->status,
    //             ]
    //         ]);
    //     }

    //     return $this->actionModules();
    // }

    // /**
    //  * Action delete modules.
    //  * 
    //  * @param string|null $id module ID. 
    //  *
    //  * @return void
    //  */
    // public function actionDeleteModules(?string $id = null): string
    // {
    //     $dataProvider = Modules::getDataProviderModules(10);
    //     $modules = [];

    //     $modules = (!is_null($id) ? [$id] : ($this->request->post('modules') ? $this->request->post('modules') : []));

    //     if (count($modules) && Modules::deleteModules($modules)) {
    //         Yii::$app->session->addFlash('toastify', [
    //             'text' => count($modules) > 1 ? 'Модули успешно удалены.' : 'Модуль успешно удален.',
    //             'type' => 'success'
    //         ]);
    //     } else {
    //         Yii::$app->session->addFlash('toastify', [
    //             'text' => count($modules) > 1 ? 'Не удалось удалить модули.' : 'Не удалось удалить модуль.',
    //             'type' => 'error'
    //         ]);
    //     }

    //     if ($this->request->isAjax) {
    //         return $this->renderAjax('modules/_modules-list', [
    //             'dataProvider' => $dataProvider,
    //         ]);
    //     }

    //     return $this->render('modules/_modules-list', [
    //         'dataProvider' => $dataProvider,
    //     ]);
    // }

    // public function actionClearModules(?string $id = null): string
    // {
    //     $dataProvider = Modules::getDataProviderModules(10);
    //     $modules = [];

    //     $modules = (!is_null($id) ? [$id] : ($this->request->post('modules') ? $this->request->post('modules') : []));

    //     if (count($modules) && Modules::clearModules($modules)) {
    //         Yii::$app->session->addFlash('toastify', [
    //             'text' => count($modules) > 1 ? 'Модули успешно очищены.' : 'Модуль успешно очищен.',
    //             'type' => 'success'
    //         ]);
    //     } else {
    //         Yii::$app->session->addFlash('toastify', [
    //             'text' => count($modules) > 1 ? 'Не удалось очистить модули.' : 'Не удалось очистить модуль.',
    //             'type' => 'error'
    //         ]);
    //     }

    //     if ($this->request->isAjax) {
    //         return $this->renderAjax('modules/_modules-list', [
    //             'dataProvider' => $dataProvider,
    //         ]);
    //     }

    //     return $this->render('modules/_modules-list', [
    //         'dataProvider' => $dataProvider,
    //     ]);
    // }

    // /**
    //  * Displays competitors page.
    //  *
    //  * @return string
    //  */
    // public function actionCompetitors()
    // {
    //     return $this->render('competitors');
    // }

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

    protected function findEvent($id)
    {
        if (($model = Events::findOne(['id' => $id])) !== null) {
            return $model;
        }

        Yii::$app->session->addFlash('toastify', [
            'text' => 'Чемпионат не найден.',
            'type' => 'error'
        ]);

        throw new NotFoundHttpException('Чемпионат не найден.');
    }

    protected function findModule($id)
    {
        if (($model = Modules::findOne(['id' => $id])) !== null) {
            return $model;
        }

        Yii::$app->session->addFlash('toastify', [
            'text' => "Модуль не найден.",
            'type' => 'error'
        ]);

        throw new NotFoundHttpException('Модуль не найден.');
    }
}
