<?php

namespace common\assets;

use yii\web\AssetBundle;
use yii\web\View;

/**
 * Cleave js asset bundle.
 */
class CleaveAsset extends AssetBundle
{
    public $sourcePath = '@common/web';
    public $js = [
        'libs/cleave.js/cleave.min.js',
    ];
    public $depends = [
        'yii\web\YiiAsset',
    ];
}
