<?php

use common\assets\AppAsset;
use common\assets\ChoicesAsset;
use common\assets\CleaveAsset;
use common\widgets\Alert;
use yii\bootstrap5\Html;
use yii\web\JqueryAsset;
use yii\web\View;
use yii\web\YiiAsset;
use yii\widgets\Pjax;

/** @var yii\web\View $this */
/** @var app\models\EventForm $model */
/** @var yii\data\ActiveDataProvider $dataProvider */
/** @var array $experts */

$this->title = 'Чемпионаты';

ChoicesAsset::register($this);
CleaveAsset::register($this);

$this->registerJsFile('@web/js/events.js', ['depends' => AppAsset::class]);
$this->registerJsFile('@web/js/pages/form-input-spin.init.js', ['depends' => AppAsset::class]);
?>

<div class="row">
    <?php Pjax::begin([
        'id' => 'pjax-create-event',
        'enablePushState' => false,
        'timeout' => 100000,
    ]); ?>
    
        <?= $this->render('_event-form', [
            'model' => $model,
            'experts' => $experts 
        ]); ?>
    
    <?php Pjax::end(); ?>
</div>

<div class="row">
    <?php Pjax::begin([
        'id' => 'pjax-events',
        'enablePushState' => false,
        'timeout' => 100000,
        'options' => [
            'data' => [
                'pjax-grid' => true
            ]
        ]
    ]); ?>

        <?= $this->render('_events-list', [
            'dataProvider' => $dataProvider,
        ]); ?>

    <?php Pjax::end(); ?>
</div>

