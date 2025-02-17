<?php

namespace common\components;

use Yii;
use yii\base\BootstrapInterface;
use yii\base\Component;
use yii\helpers\FileHelper;

class FileComponent extends Component implements BootstrapInterface
{
    public array $directories = [];

    public function bootstrap($app)
    {
        foreach ($this->directories as $directory) {
            $this->createDirectory(Yii::getAlias($directory));
        }
    }

    /**
     * Creates a new directory.
     * 
     * @param string $path path of the directory to be created. 
     * @return bool whether the directory is created successfully
     */
    public static function createDirectory(string $path): bool
    {
        return !empty($path) && !is_dir($path) && FileHelper::createDirectory($path, 0755, true);
    }

    /**
     * Removes a directory (and all its content) recursively.
     * 
     * @param string $path path of the directory to be created. 
     * @return void
     */
    public static function removeDirectory(string $path): void
    {
        if (!empty($path) && is_dir($path)) {
            FileHelper::removeDirectory($path);
        }
    }

    /**
     * Deletes the file at the specified path.
     * 
     * @param string $path the path to the file. 
     * 
     * @return bool `true` on success or `false` on failure.
     */
    public static function deleteFile(string $path): bool
    {
        if (file_exists($path)) {
            return unlink($path);
        }

        return false;
    }

    /**
     * Returns the maximum file size in bytes to download.
     * 
     * @return int|float|null
     */
    public static function getMaxSizeFiles(): int|float|null
    {
        $result = null;
        $input = trim(ini_get('upload_max_filesize'));
        $value = substr($input, 0, -1);
        $unit = strtolower(substr($input, -1));

        if (!is_numeric($value)) {
            return $result;
        }

        $value = (float)$value;

        switch ($unit) {
            case 'k':
                $result = $value * 1024;
                break;
            case 'm':
                $result = $value * 1024 * 1024;
                break;
            case 'g':
                $result = $value * 1024 * 1024 * 1024;
                break;
            default:
                if (is_numeric($input)) {
                    $result = (int)$input;
                }
        }

        return $result;
    }
}