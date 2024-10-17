<?php

namespace app\components;

use Yii;
use yii\base\Component;
use yii\helpers\FileHelper;

class FileComponent extends Component
{
    public function init()
    {
        parent::init();

        if (!is_dir(Yii::getAlias('@users'))) {
            FileHelper::createDirectory(Yii::getAlias('@users'), 0755, true);
        }
    }

    public static function createDir($dirPath = ''): string|false
    {
        if ($dirPath == '') {
            $dirPath = self::getUniqueDirPath(8);
        }

        if (FileHelper::createDirectory(Yii::getAlias('@users') . '/' . $dirPath, 0755, true)) {
            return $dirPath;
        }

        return false;
    }

    public static function deleteDir($dirPath): void
    {
        if (is_dir(Yii::getAlias('@users') . '/' . $dirPath)) {
            FileHelper::removeDirectory(Yii::getAlias('@users') . '/' . $dirPath);
        }
    }

    public static function getUniqueDirPath($length)
    {
        $dirPath = AppComponent::generateRandomString($length, ['lowercase']);
    
        while(is_dir(Yii::getAlias('@users') . '/' . $dirPath)) {
            $dirPath = Yii::$app->generationString->generateRandomString($length);
        }

        return $dirPath;
    }
}