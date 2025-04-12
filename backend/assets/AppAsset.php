<?php

namespace backend\assets;

use yii\web\AssetBundle;

/**
 * Main backend application asset bundle.
 */
class AppAsset extends AssetBundle
{
    public $basePath = '@webroot';
    public $baseUrl = '@web';
    public $css = [];
    public $js = [
        './js/commonUtils.js',
        './js/plugins/grid-checkbox-persistence.init.js',
    ];
    public $depends = [
        'yii\web\YiiAsset',
    ];
}
