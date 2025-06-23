<?php

namespace backend\assets;

use common\assets\AppAsset as AssetsAppAsset;
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
        'js/plugins/grid-checkbox-persistence.init.js',
    ];
    public $depends = [
        'yii\web\YiiAsset', AssetsAppAsset::class
    ];
}
