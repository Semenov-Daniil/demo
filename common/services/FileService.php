<?php

namespace common\services;

use common\models\Files;
use common\models\Modules;
use common\models\Students;
use Yii;
use yii\web\UploadedFile;
use yii\db\Exception;
use yii\validators\FileValidator;

class FileService
{
    const PUBLIC_DIR = 'public';
    const EVENTS_DIR = '@events';
    const STUDENTS_DIR = '@students';

    private $moduleService;

    public function __construct()
    {
        $this->moduleService = new ModuleService();
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
        $studentPaths = $this->getStudentPaths($model->events_id);

        $allSuccess = true;
        foreach ($model->files as $file) {
            $transaction = Yii::$app->db->beginTransaction();
            try {
                $fileInfo = $this->processFile($model, $file, $studentPaths);
                if (!empty($fileInfo['errors'])) {
                    $model->addError('files', ['filename' => $file->name, 'errors' => $fileInfo['errors']]);
                    $allSuccess = false;
                    $transaction->rollBack();
                } else {
                    $transaction->commit();
                }
            } catch (Exception $e) {
                $transaction->rollBack();
                $model->addError('files', ['filename' => $file->name, 'errors' => [$e->getMessage()]]);
                $this->deleteFailedFile($model, $this->getFilePath($model), $studentPaths);
                $allSuccess = false;
                Yii::error([
                    'message' => "Error processing file: " . $e->getMessage(),
                    'event_id' => $model->events_id,
                    'filename' => $file->name,
                ], __METHOD__);
            }
        }

        return $allSuccess;
    }

    public function processFile(Files $model, UploadedFile $file, array $studentPaths): array
    {
        $model->scenario = Files::SCENARIO_DEFAULT;
        $fileInfo = ['filename' => $file->name, 'errors' => []];
        $fileValidate = $this->validateFile($file);

        if (!$fileValidate['isValid']) {
            return array_merge($fileInfo, ['errors' => $fileValidate['errors']]);
        }

        $uniqueFilename = $this->getUniqueFilename($model, $file->baseName, $file->extension);
        $filePath = $this->getFilePath($model, "{$uniqueFilename}.{$file->extension}");

        if (!$file->saveAs($filePath)) {
            $fileInfo['errors'][] = "Не удалось сохранить файл {$file->name}.";
            return $fileInfo;
        }

        $model->extension = $file->extension;
        $model->name = $uniqueFilename;
        if (!$model->save()) {
            $fileInfo['errors'][] = "Не удалось сохранить запись файла {$file->name} в базе данных.";
            Yii::$app->fileComponent->deleteFile($filePath);
            return $fileInfo;
        }

        $copyErrors = $this->copyFileToStudents($filePath, "{$model->name}.{$model->extension}", $studentPaths, $model);
        if (!empty($copyErrors)) {
            $fileInfo['errors'] = array_merge($fileInfo['errors'], $copyErrors);
            $this->deleteFailedFile($model, $filePath, $studentPaths);
            return $fileInfo;
        }

        return $fileInfo;
    }

    public function copyFileToStudents(string $filePath, string $filename, array $studentPaths, Files $model): array
    {
        $errors = [];

        $moduleSubDir = $model->modules_id ? '/' . $this->moduleService->getDirectoryModuleFileTitle($model->module->number) : '';

        foreach ($studentPaths as $studentPath) {
            $basePath = Yii::getAlias($studentPath['alias']);
            $destPath = $basePath . $moduleSubDir;
            $destFile = "$destPath/$filename";

            if (!copy($filePath, $destFile)) {
                $errors[] = "Не удалось скопировать файл в {$destPath}.";
                Yii::error([
                    'message' => "Failed to copy file from {$filePath} to {$destFile}",
                    'event_id' => $model->events_id,
                    'filename' => $filename,
                ], __METHOD__);
            }
        }

        return $errors;
    }

    private function deleteFailedFile(Files $model, string $filePath, array $studentPaths): void
    {
        if ($model->name) {
            Yii::$app->fileComponent->deleteFile($filePath);
            foreach ($studentPaths as $studentPath) {
                $studentFilePath = Yii::getAlias($studentPath['alias'] . ($model->modules_id ? '/' . $this->moduleService->getDirectoryModuleFileTitle($model->module->number) : '')) . "/{$model->name}.{$model->extension}";
                Yii::$app->fileComponent->deleteFile($studentFilePath);
            }
        }
    }

    public function deleteFileEvent(string $id): bool
    {
        $transaction = Yii::$app->db->beginTransaction();
        try {
            $model = Files::findOne($id);
            if (!$model) {
                return false;
            }

            $filePath = $this->getFilePath($model);
            if (!Yii::$app->fileComponent->deleteFile($filePath)) {
                throw new Exception("Не удалось удалить файл {$filePath}.");
            }

            if (!$this->deleteFilesStudents($model)) {
                throw new Exception("Не удалось удалить файлы у студентов.");
            }

            if (!$model->delete()) {
                throw new Exception("Не удалось удалить запись файла из БД.");
            }

            $transaction->commit();
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
     * Удаляет файлы у студентов.
     */
    private function deleteFilesStudents(Files $model): bool
    {
        $students = Students::findAll(['events_id' => $model->events_id]);
        foreach ($students as $student) {
            $studentFile = $this->getStudentBasePath($student->user->login) . ($model->modules_id ? '/' . $this->moduleService->getDirectoryModuleFileTitle($model->module->number) : '') . "/{$model->name}.{$model->extension}";
            if (file_exists($studentFile) && !Yii::$app->fileComponent->deleteFile($studentFile)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Возвращает полный путь к файлу события.
     */
    private function getFilePath(Files $model, ?string $filename = null): string
    {
        $name = $filename ?? "{$model->name}.{$model->extension}";
        return $this->getEventBasePath($model) . ($model->modules_id ? '/' . $this->moduleService->getDirectoryModuleFileTitle($model->module->number) : '') . "/{$name}";
    }

    /**
     * Генерирует уникальное имя файла, добавляя суффикс при необходимости.
     */
    private function getUniqueFilename(Files $model, string $baseName, string $extension): string
    {
        $filePath = $this->getEventBasePath($model) . ($model->modules_id ? '/' . $this->moduleService->getDirectoryModuleFileTitle($model->module->number) : '') . "/{$baseName}.{$extension}";
        $counter = 1;
        $uniqueName = $baseName;

        while (file_exists($filePath) || Files::find()->where(['events_id' => $model->events_id, 'modules_id' => $model->modules_id, 'name' => $uniqueName, 'extension' => $extension])->exists()) {
            $counter++;
            $uniqueName = "{$baseName}({$counter})";
            $filePath = $this->getEventBasePath($model) . ($model->modules_id ? '/' . $this->moduleService->getDirectoryModuleFileTitle($model->module->number) : '') . "/{$uniqueName}.{$extension}";
        }

        return $uniqueName;
    }

    private function getEventBasePath(Files $model): string
    {
        return Yii::getAlias(self::EVENTS_DIR . '/' . $model->event->dir_title);
    }

    private function getStudentBasePath(string $login): string
    {
        return Yii::getAlias(self::STUDENTS_DIR . '/' . $login . '/' . self::PUBLIC_DIR);
    }

    private function getStudentPaths(int $eventId): array
    {
        return Students::find()
            ->select(['CONCAT("' . self::STUDENTS_DIR . '/", login, "/' . self::PUBLIC_DIR . '") as alias'])
            ->where(['events_id' => $eventId])
            ->joinWith('user', false)
            ->asArray()
            ->all();
    }
}