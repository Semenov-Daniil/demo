<?php

namespace common\assets;

use yii\web\AssetBundle;
use yii\web\View;

/**
 * Choices js asset bundle.
 */
class ChoicesAsset extends AssetBundle
{
    public $sourcePath = '@common/assets';
    public $js = [
        'libs/fuse.js/dist/fuse.min.js',
        'libs/choices.js/public/assets/scripts/choices.min.js',
        'js/plugins/choices-select.init.js',
    ];
    public $depends = [
        'yii\web\YiiAsset',
    ];
}
