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

        if (!is_dir(Yii::getAlias('@students'))) {
            FileHelper::createDirectory(Yii::getAlias('@students'), 0755, true);
        }
    }

    public function createDir($dirPath = ''): string|false
    {
        if ($dirPath == '') {
            $dirPath = $this->getUniqueDirPath(8);
        }

        if (FileHelper::createDirectory(Yii::getAlias('@students') . $dirPath, 0755, true)) {
            return $dirPath;
        }

        return false;
    }

    public function deleteDir($dirPath): void
    {
        if (is_dir(Yii::getAlias('@students') . $dirPath)) {
            FileHelper::removeDirectory(Yii::getAlias('@students') . $dirPath);
        }
    }

    public function getUniqueDirPath($length)
    {
        $dirPath = Yii::$app->generationString->generateRandomString($length);
    
        while(is_dir(Yii::getAlias('@students') . $dirPath)) {
            $dirPath = Yii::$app->generationString->generateRandomString($length);
        }

        return $dirPath;
    }
}