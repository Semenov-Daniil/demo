<?php

namespace common\assets;

use yii\web\AssetBundle;
use yii\web\View;

/**
 * Dropzone asset bundle.
 */
class DropzoneAsset extends AssetBundle
{
    public $sourcePath = '@common/web';
    public $css = [
        'libs/dropzone/dropzone.css',
    ];
    public $js = [
        'libs/dropzone/dropzone-min.js',
        'js/plugins/dropzone/UploadUI.js',
        'js/plugins/dropzone/UploadService.js',
        'js/plugins/dropzone/DropzoneFactory.js',
    ];
    public $depends = [
        'yii\web\YiiAsset'
    ];
}
