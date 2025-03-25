<?php

/** @var yii\web\View $this */
/** @var yii\bootstrap5\ActiveForm $form */

/** @var app\models\Modules $model */
/** @var yii\data\ActiveDataProvider $dataProvider */
/** @var array $events */
/** @var common\models\Events $event */

use common\assets\AppAsset;
use common\assets\ChoicesAsset;
use common\widgets\Alert;
use yii\bootstrap5\ActiveForm;
use yii\bootstrap5\Modal;
use yii\grid\ActionColumn;
use yii\grid\GridView;
use yii\helpers\Html;
use yii\web\View;
use yii\web\YiiAsset;
use yii\widgets\Pjax;

$this->title = 'Модули';

ChoicesAsset::register($this);

$this->registerJsFile('@web/js/modules.js', ['depends' => AppAsset::class]);

?>

<div class="row mb-4">
    <?php Pjax::begin([
        'id' => 'pjax-create-module',
        'enablePushState' => false,
        'timeout' => 10000,
    ]); ?>

        <?= $this->render('_module-create', [
            'model' => $model,
            'events' => $events
        ]); ?>
    
    <?php Pjax::end(); ?>
</div>

<div class="row">
    <?php Pjax::begin([
        'id' => 'pjax-modules',
        'enablePushState' => false,
        'timeout' => 10000,
        'options' => [
            'data' => [
                'pjax-grid' => true
            ]
        ]
    ]); ?>

        <?= $this->render('_modules-list', [
            'dataProvider' => $dataProvider,
            'event' => $event
        ])?>
    
    <?php Pjax::end(); ?>
</div>
