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
        'js/pages/grid-checkbox-persistence.init.js',
    ];
    public $depends = [
        'yii\web\YiiAsset',
    ];
}
