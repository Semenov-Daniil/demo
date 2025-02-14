<?php

namespace common\assets;

use yii\web\AssetBundle;
use yii\web\View;

/**
 * Main application asset bundle.
 */
class LoginAsset extends AssetBundle
{
    public $sourcePath = '@common/web/';
    public $css = [
        'css/bootstrap.min.css',
        'css/icons.min.css',
        'css/app.min.css',
        'css/custom.min.css'
    ];
    public $js = [
        [
            'js/layout.js',
            'position' => View::POS_HEAD,
        ],
        'libs/bootstrap/js/bootstrap.bundle.min.js',
        'libs/simplebar/simplebar.min.js',
        'libs/node-waves/waves.min.js',
        'libs/feather-icons/feather.min.js',
        'js/pages/plugins/lord-icon-2.1.0.js',

        // 'https://cdn.jsdelivr.net/npm/toastify-js',

        // 'js/toastify-js.js',
        
        'libs/choices.js/public/assets/scripts/choices.min.js',
        'libs/flatpickr/flatpickr.min.js',
        'js/pages/password-addon.init.js',
    ];
    public $depends = [
        'yii\web\YiiAsset',
        'yii\bootstrap5\BootstrapAsset'
    ];
}
