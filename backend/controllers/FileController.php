<?php

namespace backend\controllers;

use common\models\Events;
use common\models\Files;
use common\services\FileService;
use common\services\ModuleService;
use Yii;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\helpers\VarDumper;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\Response;
use yii\web\UploadedFile;

class FileController extends BaseController
{
    private FileService $fileService;
    private ModuleService $moduleService;

    public function __construct($id, $module, $config = [])
    {
        parent::__construct($id, $module, $config);
        $this->fileService = new FileService();
        $this->moduleService = new ModuleService();
    }

    public $defaultAction = 'files';

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
                    'list-files' => ['GET'],
                    'delete-files' => ['DELETE'],
                    'download' => ['GET'],
                ],
            ],
        ];
    }

    private function getEvents()
    {
        return Yii::$app->user->can('sExpert') ? Events::getExpertEvents() : Events::getEvents(Yii::$app->user->id);
    }

    /**
     * Displays files page.
     *
     * @return string
     */
    public function actionFiles(?int $event = null)
    {
        return $this->render('files', [
            'model' => new Files(['scenario' => Files::SCENARIO_UPLOAD_FILE, 'events_id' => $event]),
            'dataProvider' => Files::getDataProviderFiles($event),
            'event' => $this->findEvent($event),
            'events' => $this->getEvents(),
            'directories' => Files::getDirectories($event),
        ]);
    }

    public function actionUploadForm(?int $event = null)
    {
        return $this->renderAjaxIfRequested('_files-upload-form', [
            'model' => new Files(['scenario' => Files::SCENARIO_UPLOAD_FILE, 'events_id' => $event]),
            'events' => $this->getEvents(),
            'directories' => Files::getDirectories($event),
        ]);
    }

    public function actionUploadFiles()
    {
        $model = new Files(['scenario' => Files::SCENARIO_UPLOAD_FILE]);
        $result = ['success' => false, 'files' => []];

        if (Yii::$app->request->isPost && $model->load(Yii::$app->request->post())) {
            try {
                $model->files = UploadedFile::getInstancesByName('files');
                $count = count($model->files);

                $result['success'] = $this->fileService->processFiles($model);

                if ($result['success']) {
                    foreach ($model->files as $file) {
                        $result['files'][$file->name] = ['status' => 'success'];
                    }
                } else if (isset($model->errors['files'])) {
                    foreach ($model->errors['files'] as $error) {
                        $result['files'][$error['filename']] = [
                            'status' => 'error',
                            'errors' => $error['errors']
                        ];
                    }
                }

                Yii::$app->toast->addToast(
                    $result['success'] 
                        ? ($count > 1 ? 'Файлы успешно сохранены.' : 'Файл успешно сохранен.') 
                        : ($count > 1 ? 'Не удалось сохранить файлы.' : 'Не удалось сохранить файл.'),
                    $result['success'] ? 'success' : 'error'
                );
            } catch (\Exception $e) {
                Yii::$app->response->statusCode = 400;
                $result['files']['global'] = ['status' => 'error', 'errors' => ['Не удалось сохранить файл.']];
            }
        }

        return $this->asJson($result);
    }

    public function actionListFiles(?int $event = null): string
    {
        return $this->renderAjaxIfRequested('_files-list', [
            'dataProvider' => Files::getDataProviderFiles($event),
            'event' => $this->findEvent($event),
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
        $files = $id ? [$id] : (array) $this->request->post('files', []);
        $count = count($files);
        $result = [];

        $result['success'] = $count && $this->fileService->deleteFilesEvent($files);
        $result['message'] = $result['success'] ? 'Files deleted.' : 'Files not deleted.';

        Yii::$app->toast->addToast(
            $result['success'] 
                ? ($count > 1 ? 'Файлы успешно удалены.' : 'Файл успешно удален.') 
                : ($count > 1 ? 'Не удалось удалить файлы.' : 'Не удалось удалить файл.'),
            $result['success'] ? 'success' : 'error'
        );

        $result['code'] = Yii::$app->response->statusCode;
        return $this->asJson($result);
    }

    /**
     * File download
     * 
     * @param string $filename file name.
     */
    public function actionDownload(string|int $id)
    {
        $model = $this->findFile($id);
        $path = $this->fileService->getFilePath($model);

        if (file_exists($path)) {
            $pathArray = explode('/', $path);
            return Yii::$app->response
                ->sendStreamAsFile(fopen($path, 'r'), end($pathArray), [
                    'mimeType' => mime_content_type($path),
                    'inline' => false,
                ])
                ->send();
        }

        Yii::$app->toast->addToast('Файл не найден.', 'error');

        return $this->redirect(Yii::$app->request->referrer ?: Yii::$app->homeUrl);
    }

    protected function findFile(int|string $id): Files
    {
        if ($id && ($model = Files::findOne(['id' => $id])) !== null) {
            return $model;
        }

        Yii::$app->toast->addToast('Файл не найден.', 'error');

        throw new NotFoundHttpException('Файл не найден.');
    }

    protected function findEvent(?int $id): ?Events
    {
        return Events::findOne(['id' => $id]);
    }
}
