<?php

namespace common\assets;

use yii\web\AssetBundle;
use yii\web\View;

/**
 * Dropzone asset bundle.
 */
class DropzoneAsset extends AssetBundle
{
    public $sourcePath = '@common/assets';
    public $css = [
        'libs/dropzone/dropzone.css',
    ];
    public $js = [
        'libs/dropzone/dropzone-min.js',
    ];
    public $depends = [
        'yii\web\YiiAsset',
    ];
}
