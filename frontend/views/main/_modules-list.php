<?php

/** @var yii\web\View $this */
/** @var array $modules */

use common\assets\AppAsset;
use yii\helpers\Html;
use yii\widgets\Pjax;

?>

<div class="card overflow-hidden">
    <div class="card-header align-items-center d-flex position-relative">
        <h4 class="card-title mb-0 flex-grow-1">Модули</h4>
    </div>

    <?php Pjax::begin([
        'id' => 'pjax-modules',
        'enablePushState' => false,
        'timeout' => 10000
    ]); ?>
        <div class="card-body list-group p-0 list-group-flush">
            <?php foreach ($modules as $module): ?>
                <?= Html::a("<b>Модуль {$module['number']}:</b> http://{$module['domain']}", "http://{$module['domain']}", ['class' => 'list-group-item list-group-item-action', 'data' => ['pjax' => 0], 'target' => '_open'])?>
            <?php endforeach; ?>
        </div>
    <?php Pjax::end(); ?>
</div>