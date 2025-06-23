<?php

namespace common\services;

use common\models\Files;
use common\models\Modules;
use common\models\Students;
use Yii;
use yii\web\UploadedFile;
use yii\db\Exception;
use yii\helpers\VarDumper;
use yii\validators\FileValidator;

class FileService
{
    const FILES_DIR = '_assets';
    const PUBLIC_DIR = '_public';
    const EVENTS_DIR = '@events';
    const STUDENTS_DIR = '@students';

    private $moduleService;
    private int|null $currentFileId = null;

    public function __construct()
    {
        $this->moduleService = new ModuleService();
    }

    public function getEventChannel($id)
    {
        return Yii::$app->sse::FILE_CHANNEL . "_event_$id";
    }

    public function validateFile(UploadedFile $file): array
    {
        $validator = new FileValidator([
            'maxSize' => Yii::$app->fileComponent->getMaxSizeFiles(),
            'skipOnEmpty' => false,
        ]);
        $isValid = $validator->validate($file, $error);

        return [
            'isValid' => $isValid,
            'errors' => $error ? [$error] : [],
        ];
    }

    public function processFiles(Files $model): bool
    {
        if (!$model->validate()) {
            return false;
        }

        $model->modules_id = $model->modules_id == '0' ? null : $model->modules_id;

        $allSuccess = true;
        foreach ($model->files as $file) {
            $transaction = Yii::$app->db->beginTransaction();
            try {
                $fileInfo = $this->processFile($model, $file);
                if (empty($fileInfo['errors'])) {
                    $transaction->commit();
                    Yii::$app->sse->publish($this->getEventChannel($model->events_id), 'upload-file');
                    $this->currentFileId = null;
                } else {
                    $model->addError('files', ['filename' => $file->name, 'errors' => $fileInfo['errors']]);
                    throw new Exception("Не удалось загрузить файл.");
                }
            } catch (Exception $e) {
                $model->addError('files', ['filename' => $file->name, 'errors' => [$e->getMessage()]]);

                Yii::error("currentFileId: {$this->currentFileId}");
                if ($this->currentFileId) {
                    Yii::error("currentFileId: {$this->currentFileId}");
                    $this->deleteFileEvent($this->currentFileId);
                    $this->currentFileId = null;
                }
                
                $allSuccess = false;
                $transaction->rollBack();
                Yii::error([
                    'message' => "Error processing file: " . $e->getMessage(),
                    'event_id' => $model->events_id,
                    'filename' => $file->name,
                ], __METHOD__);
            }
        }

        return $allSuccess;
    }

    public function processFile(Files $model, UploadedFile $file): array
    {
        $model->scenario = Files::SCENARIO_DEFAULT;
        $fileInfo = ['filename' => $file->name, 'errors' => []];
        $fileValidate = $this->validateFile($file);

        if (!$fileValidate['isValid']) {
            return array_merge($fileInfo, ['errors' => $fileValidate['errors']]);
        }

        $model->extension = $file->extension;
        $model->name = $this->getUniqueFilename($model, $file->baseName, $file->extension);
        if (!$model->save()) {
            $fileInfo['errors'][] = "Не удалось сохранить запись файла {$file->name} в базе данных.";
            return $fileInfo;
        }

        $this->currentFileId = $model->id;
        Yii::info("Current file id: {$this->currentFileId}");
        
        $filePath = $this->getFilePath($model);
        if (!$file->saveAs($filePath)) {
            $fileInfo['errors'][] = "Не удалось сохранить файл {$file->name}.";
            return $fileInfo;
        }

        return $fileInfo;
    }

    public function deleteFileEvent(string|int $id): bool
    {
        $transaction = Yii::$app->db->beginTransaction();
        try {
            $model = Files::findOne(['id' => $id]);
            if (!$model) {
                return false;
            }

            $filePath = $this->getFilePath($model);
            Yii::error("file path: {$filePath}");
            if (!Yii::$app->fileComponent->deleteFile($filePath)) {
                throw new Exception("Не удалось удалить файл {$filePath}.");
            }

            if (!$model->delete()) {
                throw new Exception("Не удалось удалить запись файла из БД.");
            }

            $transaction->commit();
            Yii::$app->sse->publish($this->getEventChannel($model->events_id), 'delete-file');
            return true;
        } catch (Exception $e) {
            $transaction->rollBack();
            Yii::error([
                'message' => "Error deleting file: " . $e->getMessage(),
                'file_id' => $id,
            ], __METHOD__);
            return false;
        }
    }

    /**
     * Удаляет несколько файлов.
     */
    public function deleteFilesEvent(array $fileIds): bool
    {
        foreach ($fileIds as $id) {
            if (!$this->deleteFileEvent($id)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Возвращает полный путь к файлу события.
     */
    public function getFileDirectory(Files $model): string
    {
        return $this->getEventBasePath($model) . '/' . ($model->modules_id ? $this->moduleService->getDirectoryModuleFileTitle($model->module->number, $model->module->status) : self::PUBLIC_DIR);
    }

    /**
     * Возвращает полный путь к файлу события.
     */
    public function getFilePath(Files $model): string
    {
        return $this->getFileDirectory($model) . "/{$model->name}.{$model->extension}";
    }

    /**
     * Генерирует уникальное имя файла, добавляя суффикс при необходимости.
     */
    private function getUniqueFilename(Files $model, string $baseName, string $extension): string
    {
        $fileDir = $this->getFileDirectory($model);
        $filePath = "{$fileDir}/{$baseName}.{$extension}";
        $counter = 1;
        $uniqueName = $baseName;

        while (file_exists($filePath) || Files::find()->where(['events_id' => $model->events_id, 'modules_id' => $model->modules_id, 'name' => $uniqueName, 'extension' => $extension])->exists()) {
            $counter++;
            $uniqueName = "{$baseName}({$counter})";
            $filePath = "{$fileDir}/{$uniqueName}.{$extension}";
        }

        return $uniqueName;
    }

    public static function getEventBasePath(Files $model): string
    {
        return Yii::getAlias(self::EVENTS_DIR . '/' . $model->event->dir_title);
    }

    private function getStudentBasePath(string $login): string
    {
        return Yii::getAlias(self::STUDENTS_DIR . '/' . $login . '/' . self::FILES_DIR);
    }

    private function getStudentPaths(int $eventId): array
    {
        return Students::find()
            ->select(['CONCAT("' . self::STUDENTS_DIR . '/", login, "/' . self::FILES_DIR . '") as alias'])
            ->where(['events_id' => $eventId])
            ->joinWith('user', false)
            ->asArray()
            ->all();
    }
}