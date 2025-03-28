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
        'js/pages/cleave.init.js',
    ];
    public $depends = [
        'common\assets\AppAsset',
    ];
}
