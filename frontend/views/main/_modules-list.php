<?php

/** @var yii\web\View $this */
/** @var array $modules */

use common\assets\AppAsset;
use yii\helpers\Html;
use yii\widgets\Pjax;

?>

<div class="card">
    <div class="card-header align-items-center d-flex position-relative border-bottom-0">
        <h4 class="card-title mb-0 flex-grow-1">Модули</h4>
    </div>

    <div class="card-body list-group p-0 list-group-flush">
        <?php Pjax::begin([
            'id' => 'pjax-modules',
            'enablePushState' => false,
            'timeout' => 10000
        ]); ?>
            <?php foreach ($modules as $module): ?>
                <?= Html::a("<b>Модуль {$module['number']}:</b> http://{$module['domain']}", "http://{$module['domain']}", ['class' => 'list-group-item list-group-item-action', 'data' => ['pjax' => 0], 'target' => '_blank'])?>
            <?php endforeach; ?>
        <?php Pjax::end(); ?>
    </div>
</div>