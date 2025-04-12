<?php

namespace backend\assets;

use common\assets\DropzoneAsset;
use yii\web\AssetBundle;

/**
 * Backend dropzone asset bundle.
 */
class DropzoneAsset extends AssetBundle
{
    public $basePath = '@webroot';
    public $baseUrl = '@web';
    public $css = [];
    public $js = [
        './js/dropzone/UploadService.js',
        './js/dropzone/UploadUI.js',
        './js/dropzone/DropzoneFactory.js',
    ];
    public $depends = [
        AppAsset::class,
        DropzoneAsset::class,
    ];
}
