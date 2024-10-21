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

    public static function createDir($dir): string|false
    {
        return !empty($dir) && FileHelper::createDirectory(Yii::getAlias('@users') . '/' . $dir, 0755, true);
    }

    public static function deleteDir($dir): void
    {
        if (!empty($dir) && is_dir(Yii::getAlias('@users') . '/' . $dir)) {
            FileHelper::removeDirectory(Yii::getAlias('@users') . '/' . $dir);
        }
    }
}