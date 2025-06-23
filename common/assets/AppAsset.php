<?php

namespace common\assets;

use yii\web\AssetBundle;
use yii\web\View;

/**
 * Main application asset bundle.
 */
class AppAsset extends AssetBundle
{
    public $sourcePath = '@common/web';
    public $css = [
        'libs/noty/lib/noty.css',
        'css/noty-team-vz.css',
        'css/bootstrap.min.css',
        'css/icons.min.css',
        'css/app.css',
        'css/custom.min.css',
    ];
    public $js = [
        [
            'js/layout.js',
            'position' => \yii\web\View::POS_HEAD
        ],
        'libs/bootstrap/js/bootstrap.bundle.min.js',
        'libs/simplebar/simplebar.min.js',
        'libs/node-waves/waves.min.js',
        'libs/noty/lib/noty.js',
        'js/alert-toastify.js',
        'js/plugins/commonUtils.js',
        'js/app.js',
    ];
    public $depends = [
        'yii\web\YiiAsset',
    ];
}
