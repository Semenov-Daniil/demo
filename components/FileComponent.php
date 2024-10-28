<?php

namespace app\components;

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
        return !empty($path) && FileHelper::createDirectory($path, 0755, true);
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
}