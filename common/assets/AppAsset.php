<?php

namespace common\assets;

use yii\web\AssetBundle;
use yii\web\View;

/**
 * Main application asset bundle.
 */
class AppAsset extends AssetBundle
{
    public $sourcePath = '@common/web/';
    public $css = [
        'libs/jsvectormap/jsvectormap.min.css',
        'libs/swiper/swiper-bundle.min.css',
        'css/bootstrap.min.css',
        'css/icons.min.css',
        'css/app.css',
        'css/custom.min.css'
    ];
    public $js = [
        // [
        //     'js/layout.js',
        //     'position' => View::POS_HEAD,
        // ],
        'libs/bootstrap/js/bootstrap.bundle.min.js',
        'libs/simplebar/simplebar.min.js',
        'libs/node-waves/waves.min.js',
        'libs/feather-icons/feather.min.js',
        'js/pages/plugins/lord-icon-2.1.0.js',

        // 'https://cdn.jsdelivr.net/npm/toastify-js',

        // 'js/toastify-js.js',
        
        'libs/choices.js/public/assets/scripts/choices.min.js',
        'libs/flatpickr/flatpickr.min.js',
        'libs/apexcharts/apexcharts.min.js',
        'libs/jsvectormap/jsvectormap.min.js',
        'libs/jsvectormap/maps/world-merc.js',
        'libs/swiper/swiper-bundle.min.js',
        // 'js/pages/dashboard-ecommerce.init.js',
        'js/app.js'
    ];
    public $depends = [
        'yii\web\YiiAsset',
        'yii\bootstrap5\BootstrapAsset'
    ];
}
