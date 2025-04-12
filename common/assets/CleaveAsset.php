<?php

namespace common\assets;

use yii\web\AssetBundle;
use yii\web\View;

/**
 * Cleave js asset bundle.
 */
class CleaveAsset extends AssetBundle
{
    public $sourcePath = '@common/assets';
    public $js = [
        'libs/cleave.js/cleave.min.js',
        'js/plugins/cleave.init.js',
    ];
    public $depends = [
        'yii\web\YiiAsset',
    ];
}
