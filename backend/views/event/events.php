<?php

use backend\assets\AppAsset as BackendAppAsset;
use common\assets\AppAsset;
use common\assets\ChoicesAsset;
use common\assets\CleaveAsset;
use common\assets\InputStepAsset;
use common\widgets\Alert;
use yii\bootstrap5\Html;
use yii\bootstrap5\Modal;
use yii\web\JqueryAsset;
use yii\web\View;
use yii\web\YiiAsset;
use yii\widgets\Pjax;

/** @var yii\web\View $this */
/** @var app\models\EventForm $model */
/** @var yii\data\ActiveDataProvider $dataProvider */
/** @var array $experts */

$this->title = 'Чемпионаты';

BackendAppAsset::register($this);
ChoicesAsset::register($this);
CleaveAsset::register($this);
InputStepAsset::register($this);

?>

<div class="row">
    <?php Pjax::begin([
        'id' => 'pjax-create-event',
        'enablePushState' => false,
        'timeout' => 100000,
    ]); ?>
    
        <?= $this->render('_event-create', [
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

<?php Modal::begin([
    'id' => 'modal-update-event',
    'size' => Modal::SIZE_DEFAULT,
    'title' => 'Редактирование чемпионата',
    'centerVertical' => true,
    'headerOptions' => [
        'class' => 'bg-light p-3',
    ]
]); ?>

<div class="row">
    <div class="d-flex flex-column justify-content-end mb-3 placeholder-glow">
        <div class="mr-lg-3 placeholder col-4 mb-2 rounded-1"></div>
        <div class="form-control placeholder p-3"></div>
    </div>
    <div class="d-flex flex-column justify-content-end mb-3 placeholder-glow">
        <div class="mr-lg-3 placeholder col-2 mb-2 rounded-1"></div>
        <div class="form-control placeholder p-3"></div>
    </div>
    <div class="col-12 d-flex flex-wrap gap-2 justify-content-end">
        <button type="button" class="btn btn-info disabled placeholder col-2" data-bs-dismiss="modal"></button>
        <button type="submit" class="btn btn-success disabled placeholder col-3"></button>
    </div>
</div>

<?php Modal::end(); ?>

