<?php

namespace backend\controllers;

use common\models\Events;
use common\models\Students;
use common\services\StudentService;
use Yii;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\helpers\BaseConsole;
use yii\helpers\VarDumper;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\Response;
use ZipArchive;

class StudentDataController extends BaseController
{
    private StudentService $studentService;

    public function __construct($id, $module, $config = [])
    {
        parent::__construct($id, $module, $config);
        $this->studentService = new StudentService();
    }

    public $defaultAction = 'students';

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
                    'students' => ['GET'],
                    'list-students' => ['GET'],
                    'download-archive' => ['GET'],
                ],
            ],
        ];
    }

    private function getEvents()
    {
        return Yii::$app->user->can('sExpert') ? Events::getExpertEvents() : Events::getEvents(Yii::$app->user->id);
    }

    /**
     * Displays students data page.
     *
     * @return string
     */
    public function actionStudents(?int $event = null): string
    {
        return $this->render('students', [
            'dataProvider' => Students::getDataProviderStudents($event, true),
            'events' => $this->getEvents(),
            'event' => $this->findEvent($event),
        ]);
    }

    public function actionListStudents(?string $event = null): string
    {
        return $this->renderAjaxIfRequested('_students-list', [
            'dataProvider' => Students::getDataProviderStudents($event, true), 
            'event' => $this->findEvent($event),
        ]);
    }

    public function actionDownloadArchive(int $student, string $folderTitle = 'all')
    {
        $modelStudent = $this->findStudent($student);

        try {
            $folders = $this->studentService->getFolders($modelStudent->students_id, $folderTitle);

            $zipFileName = 'student_' . Yii::$app->fileComponent->sanitizeFileName($modelStudent->user->fullName) . '_' . $folderTitle . '_' . date('d-m-Y') . '.zip';
            $zipFilePath = Yii::getAlias("@runtime/{$zipFileName}");

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
            $this->addFlashMessage('Не удалось скачать архив.', 'error');
    
            return $this->redirect(Yii::$app->request->referrer ?: Yii::$app->homeUrl);
        }
    }

    protected function findStudent(?int $id): ?Students
    {
        if ($id && ($model = Students::findOne(['students_id' => $id])) !== null) {
            return $model;
        }

        $this->addFlashMessage('Студент не найден.', 'error');

        throw new NotFoundHttpException('Студент не найден.');
    }

    protected function findEvent(?int $id): ?Events
    {
        return Events::findOne(['id' => $id]);
    }
}
