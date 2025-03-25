<?php

namespace backend\controllers;

use common\models\Events;
use common\models\Students;
use Yii;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\helpers\VarDumper;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\Response;
use ZipArchive;

class ParticipantController extends Controller
{
    public $defaultAction = 'participants';

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
                    'participants' => ['GET'],
                    'all-participants' => ['GET'],
                    'download-archive' => ['GET'],
                ],
            ],
        ];
    }

    /**
     * Displays participants page.
     *
     * @return string
     */
    public function actionParticipants(?int $event = null): string
    {
        $dataProvider = Students::getDataProviderStudents($event, true);
        $dataProvider->pagination->route = 'participants';

        return $this->render('participants', [
            'dataProvider' => $dataProvider,
            'events' => Yii::$app->user->can('sExpert') ? Events::getExpertEvents() : Events::getEvents(Yii::$app->user->id),
            'event' => Events::findOne(['id' => $event]),
        ]);
    }

    public function actionAllParticipants(?string $event = null): string
    {
        $dataProvider = Students::getDataProviderStudents($event, true);
        $dataProvider->pagination->route = 'participants';
        $modelEvent = Events::findOne(['id' => $event]);

        session_write_close();

        if ($this->request->isAjax) {
            return $this->renderAjax('_participants-list', [
                'dataProvider' => $dataProvider,
                'event' => $modelEvent,
            ]);
        }

        return $this->render('_participants-list', [
            'dataProvider' => $dataProvider,
            'event' => $modelEvent,
        ]);
    }

    public function actionDownloadArchive(int $student, string $folderTitle = 'all')
    {
        $student = $this->findStudent($student);

        try {
            $folders = $student->getFolders($folderTitle);

            $zipFileName = 'student_' . $student->user->surname . '_' . $student->user->name . ($student->user?->patronymic ? '_' . $student->user->patronymic : '') . '_' . $folderTitle . '_' . date('d-m-Y') . '.zip';
            $zipFilePath = \Yii::getAlias('@runtime') . '/' . $zipFileName;

            $zip = new ZipArchive();
            if ($zip->open($zipFilePath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
                throw new \Exception('Не удалось создать архив');
            }

            foreach ($folders as $folderPath) {
                if (!is_dir($folderPath)) {
                    continue;
                }

                $dirIterator = new \RecursiveDirectoryIterator($folderPath);
                $iterator = new \RecursiveIteratorIterator($dirIterator, \RecursiveIteratorIterator::SELF_FIRST);

                foreach ($iterator as $item) {
                    $itemPath = $item->getRealPath();
                    $relativePath = substr($itemPath, strlen($folderPath) + 1);
                    $folderName = basename($folderPath);
                    $zipPath = $folderName . '/' . $relativePath;

                    if ($item->isDir()) {
                        $zip->addEmptyDir($zipPath);
                        // if ($relativePath !== '') {
                        // }
                    } else {
                        $zip->addFile($itemPath, $zipPath);
                    }
                }
            }

            if (!$zip->close()) {
                throw new \Exception('Не удалось записать архив: ' . $zipFilePath);
            }
        
            if (!file_exists($zipFilePath)) {
                throw new \Exception('Файл не найден после закрытия: ' . $zipFilePath);
            }

            return Yii::$app->response
                ->sendStreamAsFile(fopen($zipFilePath, 'rb'), $zipFileName, [
                    'mimeType' => 'application/zip',
                    'inline' => false,
                ])
                ->on(Response::EVENT_AFTER_SEND, function () use ($zipFilePath) {
                    unlink($zipFilePath);
                });
        } catch (\Exception $e) {
            Yii::$app->session->addFlash('toastify', [
                'text' => 'Не удалось скачать архив.',
                'type' => 'error'
            ]);
    
            return $this->redirect(Yii::$app->request->referrer ?: Yii::$app->homeUrl);
        }
    }

    protected function findStudent($id)
    {
        if (($model = Students::findOne(['students_id' => $id])) !== null) {
            return $model;
        }

        Yii::$app->session->addFlash('toastify', [
            'text' => "Студент не найден.",
            'type' => 'error'
        ]);

        throw new NotFoundHttpException('Студент не найден.');
    }
}
