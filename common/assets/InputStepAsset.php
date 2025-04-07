<?php

namespace common\assets;

use yii\web\AssetBundle;
use yii\web\View;

/**
 * Input step js asset bundle.
 */
class InputStepAsset extends AssetBundle
{
    public $sourcePath = '@common/assets';
    public $js = [
        'js/plugins/input-step.init.js',
    ];
    public $depends = [
        'common\assets\CleaveAsset',
    ];
}
