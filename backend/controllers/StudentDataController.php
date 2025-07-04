<?php

namespace backend\controllers;

use common\models\EncryptedPasswords;
use common\models\Events;
use common\models\Statuses;
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
            'dataProvider' => Students::getDataProviderStudents($event, true, 'student-data', 9),
            'events' => $this->getEvents(),
            'event' => $this->findEvent($event),
        ]);
    }

    public function actionAllEvents()
    {
        $result = ['hasGroup' => false, 'events' => []];
        $eventsList = $this->getEvents();
        if (Yii::$app->user->can('sExpert')) {
            $result['hasGroup'] = true;
            $result['events'] = array_map(function($groupLabel, $group) {
                return ['group' => $groupLabel, 'items' => array_map(function($id, $name) {
                    return ['value' => $id, 'label' => $name];
                }, array_keys($group), $group)];
            }, array_keys($eventsList), $eventsList);
        } else {
            $result['events'] = array_map(function($id, $name) {
                return ['value' => $id, 'label' => $name];
            }, array_keys($eventsList), $eventsList);
        }

        return $this->asJson($result);
    }

    public function actionListStudents(?string $event = null): string
    {
        return $this->renderAjaxIfRequested('_students-list', [
            'dataProvider' => Students::getDataProviderStudents($event, true, 'student-data', 9), 
            'event' => $this->findEvent($event),
        ]);
    }

    public function actionDownloadArchive(int $student, int|null $module = null)
    {
        $modelStudent = $this->findStudent($student);

        try {
            $dirs = $this->studentService->getDirectories($modelStudent->students_id, $module);
            $dbs = $this->studentService->getDatabases($modelStudent->students_id, $module);

            $zipFileName = 'student_' . Yii::$app->fileComponent->sanitizeFileName($modelStudent->user->fullName) . '_module' . ($module ?: '_all') . '_' . date('d-m-Y') . '.zip';
            $zipFilePath = Yii::getAlias("@runtime/{$zipFileName}");

            $zip = new ZipArchive();
            if ($zip->open($zipFilePath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
                throw new \Exception('Не удалось создать архив');
            }

            $login = $modelStudent->user->login;
            $password = EncryptedPasswords::decryptByPassword($modelStudent->encryptedPassword->encrypted_password);
            $dbPaths = [];
            foreach ($dirs as $key => $dirPath) {
                if (!is_dir($dirPath)) {
                    continue;
                }

                $dirIterator = new \RecursiveDirectoryIterator($dirPath);
                $iterator = new \RecursiveIteratorIterator($dirIterator, \RecursiveIteratorIterator::SELF_FIRST);

                $folderName = basename($dirPath);

                foreach ($iterator as $item) {
                    $itemPath = $item->getRealPath();
                    $relativePath = substr($itemPath, strlen($dirPath) + 1);
                    $zipPath = $folderName . '/' . $relativePath;

                    if ($item->isDir()) {
                        $zip->addEmptyDir($zipPath);
                    } else {
                        $zip->addFile($itemPath, $zipPath);
                    }
                }

                $sqlFile = $this->dumpDb($dbs[$key], Yii::$app->db->username, Yii::$app->db->password);
    
                if ($sqlFile && file_exists($sqlFile)) {
                    $zip->addFile($sqlFile, $folderName . '/' . basename($sqlFile));
                }

                $dbPaths[] = $sqlFile;
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
                ->on(Response::EVENT_AFTER_SEND, function () use ($zipFilePath, $dbPaths) {
                    unlink($zipFilePath);
                    array_map(function ($path) {
                        unlink($path);
                    }, $dbPaths);
                });
        } catch (\Exception $e) {
            VarDumper::dump( $e, $depth = 10, $highlight = true);die;
            Yii::$app->toast->addToast('Не удалось скачать архив.', 'error');
            Yii::error("\nFailed to send archive:\n{$e->getMessage()}", __METHOD__);
            return $this->redirect(Yii::$app->request->referrer ?: Yii::$app->homeUrl);
        }
    }

    protected function dumpDb(string $db, string $login, string $password): string|bool
    {
        try {
            $host = Yii::$app->dbComponent->getHost();
            $sqlFile = Yii::getAlias("@runtime/{$db}.sql");

            $command = sprintf(
                'mysqldump --host=%s --user=%s --password=%s %s > %s',
                escapeshellarg($host),
                escapeshellarg($login),
                escapeshellarg($password),
                escapeshellarg($db),
                escapeshellarg($sqlFile)
            );

            exec($command, $output, $resultCode);

            if ($resultCode !== 0 || !file_exists($sqlFile)) {
                throw new \Exception("Failed to execute mysqldump");
            }

            return $sqlFile;
        } catch (\Exception $e) {
            Yii::error("\nDatabase dump failed:\n{$e->getMessage()}", __METHOD__);
            throw $e;
        }
    }

    protected function findStudent(?int $id): ?Students
    {
        $student = Students::find()
            ->joinWith('user', false)
            ->where([
                'students_id' => $id, 
                'statuses_id' => [
                    Statuses::getStatusId(Statuses::CONFIGURING),
                    Statuses::getStatusId(Statuses::READY)
                ]
            ])
            ->one()
        ;
        if ($student !== null) {
            return $student;
        }

        Yii::$app->toast->addToast('Студент не найден.', 'error');

        throw new NotFoundHttpException('Студент не найден.');
    }

    protected function findEvent(int|string|null $id = null): ?Events
    {
        return Events::find()
            ->where([
                'id' => $id,
                'statuses_id' => [
                    Statuses::getStatusId(Statuses::CONFIGURING),
                    Statuses::getStatusId(Statuses::READY),
                ]
            ])
            ->one()
        ;
    }
}
