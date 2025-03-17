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
    public function actionFiles()
    {
        $model = new FilesEvents(['scenario' => FilesEvents::SCENARIO_UPLOAD_FILE]);
        $dataProvider = $model->getDataProviderFiles(Yii::$app->user->identity->event->id);

        return $this->render('files/files', [
            'model' => $model,
            'dataProvider' => $dataProvider,
            'events' => Yii::$app->user->can('sExpert') ? Events::getExpertEvents() : Events::getEvents(Yii::$app->user->id),
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
                $result = $model->processFiles(Yii::$app->user->identity->event->id); 
            } catch (\Exception $e) {
                Yii::$app->response->statusCode = 400;
                $error = $e->getMessage();
            }    
        }

        if (empty($result) && empty($error) && !$model->hasErrors()) {
            Yii::$app->session->addFlash('toastify', [
                'text' => count($model->files) > 1 ? 'Файлы успешно загружены.' : 'Файл успешно загружен.',
                'type' => 'success'
            ]);
        } else {
            Yii::$app->session->addFlash('toastify', [
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
            return $this->renderAjax('files/_files-form', [
                'model' => $model,
            ]);
        }

        return $this->render('files/_files-form', [
            'model' => $model,
        ]);
    }

    public function actionAllFiles(): string
    {
        $dataProvider = FilesEvents::getDataProviderFiles(Yii::$app->user->identity->event->id);

        session_write_close();

        if ($this->request->isAjax) {
            return $this->renderAjax('files/_files-list', [
                'dataProvider' => $dataProvider,
            ]);
        }

        return $this->render('files/_files-list', [
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
        $dataProvider = FilesEvents::getDataProviderFiles(Yii::$app->user->identity->event->id);
        $files = [];

        $files = (!is_null($id) ? [$id] : ($this->request->post('files') ? $this->request->post('files') : []));

        if (count($files) && FilesEvents::deleteFilesEvent($files)) {
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

        if ($this->request->isAjax) {
            return $this->renderAjax('files/_files-list', [
                'dataProvider' => $dataProvider,
            ]);
        }

        return $this->render('files/_files-list', [
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

        Yii::$app->session->addFlash('toastify', [
            'text' => 'Файл не найден.',
            'type' => 'error'
        ]);

        return $this->redirect(Yii::$app->request->referrer ?: Yii::$app->homeUrl);
    }
}
