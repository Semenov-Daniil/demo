<?php

namespace common\assets;

use yii\web\AssetBundle;
use yii\web\View;

/**
 * Main application asset bundle.
 */
class AppAsset extends AssetBundle
{
    public $sourcePath = '@common/assets';
    public $css = [
        // aos
        // 'libs/aos/aos.css',

        // 'libs/toastr/toastr.css',
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
        'libs/node-waves/waves.min.js',
        'libs/feather-icons/feather.min.js',
        'js/pages/plugins/lord-icon-2.1.0.js',

        'libs/noty/lib/noty.js',
        
        'libs/choices.js/public/assets/scripts/choices.min.js',
        'libs/flatpickr/flatpickr.min.js',
        'libs/apexcharts/apexcharts.min.js',
        'libs/jsvectormap/jsvectormap.min.js',
        'libs/jsvectormap/maps/world-merc.js',
        'libs/swiper/swiper-bundle.min.js',
        // 'js/pages/dashboard-ecommerce.init.js',
        'js/alert-toastify.js',
        'js/gridview.js',
        'js/app.js'
    ];
    public $depends = [
        'yii\web\YiiAsset',
    ];
}
