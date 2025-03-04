<?php

namespace common\assets;

use yii\web\AssetBundle;
use yii\web\View;

/**
 * Main application asset bundle.
 */
class LoginAsset extends AssetBundle
{
    public $sourcePath = '@common/assets';
    public $css = [
        'css/bootstrap.min.css',
        'css/icons.min.css',
        'css/app.css',
        'css/custom.min.css',
    ];
    public $js = [
        'libs/bootstrap/js/bootstrap.bundle.min.js',
        'libs/simplebar/simplebar.min.js',
        'js/pages/plugins/lord-icon-2.1.0.js',
    ];
    public $depends = [
        'yii\web\YiiAsset',
        'yii\bootstrap5\BootstrapAsset'
    ];
}
