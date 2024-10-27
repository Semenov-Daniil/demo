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

    public static function createDirectory($dir): string|false
    {
        return !empty($dir) && FileHelper::createDirectory($dir, 0755, true);
    }

    public static function removeDirectory($dir): void
    {
        if (!empty($dir) && is_dir($dir)) {
            FileHelper::removeDirectory($dir);
        }
    }
}