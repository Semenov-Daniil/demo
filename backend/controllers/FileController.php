<?php

namespace backend\controllers;

use common\models\Events;
use common\models\Files;
use Yii;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\helpers\VarDumper;
use yii\web\Controller;
use yii\web\Response;
use yii\web\UploadedFile;

class FileController extends Controller
{
    public $defaultAction = 'modules';

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
                    'files' => ['GET'],
                    'upload-form' => ['GET'],
                    'upload-files' => ['POST'],
                    'all-files' => ['GET'],
                    'delete-files' => ['DELETE'],
                    'download' => ['GET'],
                ],
            ],
        ];
    }

    /**
     * Displays files page.
     *
     * @return string
     */
    public function actionFiles(?int $event = null)
    {
        $model = new Files(['scenario' => Files::SCENARIO_UPLOAD_FILE, 'events_id' => $event]);
        $dataProvider = $model->getDataProviderFiles($event);
        $directories = Files::getDirectories($event);
        $events = Yii::$app->user->can('sExpert') ? Events::getExpertEvents() : Events::getEvents(Yii::$app->user->id);
        $modelEvent = Events::findOne(['id' => $event]);

        if ($this->request->isAjax) {
            return $this->renderAjax('files', [
                'model' => $model,
                'dataProvider' => $dataProvider,
                'event' => $modelEvent,
                'events' => $events,
                'directories' => $directories,
            ]);
        }

        return $this->render('files', [
            'model' => $model,
            'dataProvider' => $dataProvider,
            'event' => $modelEvent,
            'events' => $events,
            'directories' => $directories,
        ]);
    }

    public function actionUploadForm(?int $event = null)
    {
        $model = new Files(['scenario' => Files::SCENARIO_UPLOAD_FILE, 'events_id' => $event]);
        $events = Yii::$app->user->can('sExpert') ? Events::getExpertEvents() : Events::getEvents(Yii::$app->user->id);
        $directories = Files::getDirectories($event);

        if ($this->request->isAjax) {
            return $this->renderAjax('_files-form', [
                'model' => $model,
                'events' => $events,
                'directories' => $directories,
            ]);
        }

        return $this->render('_files-form', [
            'model' => $model,
            'events' => $events,
            'directories' => $directories,
        ]);
    }

    public function actionUploadFiles()
    {
        $model = new Files(['scenario' => Files::SCENARIO_UPLOAD_FILE]);
        $result = ['success' => true, 'files' => []];

        if (Yii::$app->request->isPost) {
            $data = Yii::$app->request->post();
            try {
                if ($model->load($data)) {
                    $model->files = UploadedFile::getInstancesByName('files');
                    if ($model->processFiles()) {
                        foreach ($model->files as $file) {
                            $result['files'][$file->name] = ['status' => 'success'];
                        }

                        Yii::$app->session->addFlash('toastify', [
                            'text' => count($model->files) > 1 ? 'Файлы успешно загружены.' : 'Файл успешно загружен.',
                            'type' => 'success'
                        ]);
                    }
                    if ($model->hasErrors()) {
                        $result['success'] = false;
                        foreach ($model->errors['files'] as $error) {
                            $result['files'][$error['filename']] = [
                                'status' => 'error',
                                'errors' => $error['errors']
                            ];
                        }

                        Yii::$app->session->addFlash('toastify', [
                            'text' => count($model->files) > 1 ? 'Не удалось загрузить файлы.' : 'Не удалось загрузить файл.',
                            'type' => 'error'
                        ]);
                    }
                }
            } catch (\Exception $e) {
                Yii::$app->response->statusCode = 400;
                $result['success'] = false;
                $result['files']['global'] = ['status' => 'error', 'errors' => [$e->getMessage()]];
            }
        }

        return $this->asJson([
            'data' => $result
        ]);
    }

    public function actionAllFiles(?int $event = null): string
    {
        $dataProvider = Files::getDataProviderFiles($event);
        $event = Events::findOne(['id' => $event]);

        session_write_close();

        if ($this->request->isAjax) {
            return $this->renderAjax('_files-list', [
                'dataProvider' => $dataProvider,
                'event' => $event
            ]);
        }

        return $this->render('_files-list', [
            'dataProvider' => $dataProvider,
            'event' => $event
        ]);
    }

    /**
     * Action delete students.
     * 
     * @param string|null $id student ID. 
     *
     * @return void
     */
    public function actionDeleteFiles(?string $id = null): Response
    {
        $files = [];
        $result = [];

        $files = (!is_null($id) ? [$id] : ($this->request->post('files') ? $this->request->post('files') : []));

        if (count($files) && $result['success'] = Files::deleteFilesEvent($files)) {
            Yii::$app->session->addFlash('toastify', [
                'text' => count($files) > 1 ? 'Файлы успешно удалены.' : 'Файл успешно удален.',
                'type' => 'success'
            ]);
        } else {
            Yii::$app->session->addFlash('toastify', [
                'text' => count($files) > 1 ? 'Не удалось удалить файлы.' : 'Не удалось удалить файл.',
                'type' => 'error'
            ]);
        }

        $result['code'] = Yii::$app->response->statusCode;

        return $this->asJson([
            'data' => $result
        ]);
    }

    /**
     * File download
     * 
     * @param string $filename file name.
     * @param string $event the name of the event directory.
     */
    public function actionDownload(string $event, string $filename)
    {
        $dir = Events::findOne(['id' => $event])?->dir_title;
        if ($file = Files::findFile($event, $filename)) {
            $filePath = Yii::getAlias("@events/{$dir}/" . (is_null($file['modules_id']) ? '' : Events::getDirectoryModuleFileTitle($file['number']) . '/') . "{$file['filename']}");
    
            if (file_exists($filePath)) {
                session_write_close();
                return Yii::$app->response
                    ->sendStreamAsFile(fopen($filePath, 'r'), $file['filename'], [
                        'mimeType' => mime_content_type($filePath),
                        'inline' => false,
                    ])
                    ->send();
            }
        }

        Yii::$app->session->addFlash('toastify', [
            'text' => 'Файл не найден.',
            'type' => 'error'
        ]);

        return $this->redirect(Yii::$app->request->referrer ?: Yii::$app->homeUrl);
    }
}
