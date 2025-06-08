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
        [
            'js/layout.js',
            'position' => \yii\web\View::POS_HEAD
        ],
        'libs/bootstrap/js/bootstrap.bundle.min.js',
    ];
    public $depends = [
        'yii\web\YiiAsset',
    ];
}
