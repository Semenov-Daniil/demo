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
        'libs/noty/lib/noty.css',
        'css/noty-team-vz.css',
        'css/bootstrap.min.css',
        'css/icons.min.css',
        'css/app.css',
        'css/custom.min.css',
    ];
    public $js = [
        'libs/bootstrap/js/bootstrap.bundle.min.js',
        'libs/simplebar/simplebar.min.js',
        'js/pages/plugins/lord-icon-2.1.0.js',
        'libs/noty/lib/noty.js',
        'js/alert-toastify.js',
        'js/gridview.js',
        'js/app.js'
    ];
    public $depends = [
        'yii\web\YiiAsset',
        'yii\bootstrap5\BootstrapAsset'
    ];
}
