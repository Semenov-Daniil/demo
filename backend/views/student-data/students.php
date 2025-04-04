<?php

/** @var yii\web\View $this */

/** @var app\models\Students $model */
/** @var yii\data\ActiveDataProvider $dataProvider */
/** @var array $events */
/** @var common\models\Events|null $event */

use backend\assets\AppAsset as BackendAppAsset;
use common\assets\AppAsset;
use common\assets\ChoicesAsset;
use yii\helpers\Html;
use yii\widgets\Pjax;

$this->title = 'Участники';

BackendAppAsset::register($this);
ChoicesAsset::register($this);

$this->registerJsFile('@web/js/modules/student-data/student-data.js', ['depends' => BackendAppAsset::class]);

?>

<div class="row mb-4">
    <div class="col-12 field-choices">
        <?= Html::label('Чемпионат', 'events-select'); ?>
        <?= Html::dropDownList('events-select', $event?->id, $events, [
            'id' => 'events-select',
            'prompt' => 'Выберите чемпионат', 
            'data' => [
                'choices' => true, 
                'choices-group' => true, 
                'choices-removeItem' => true
            ], 
            'class' => 'form-select',
        ]); ?>
    </div>
</div>

<div class="row">
    <?php Pjax::begin([
        'id' => 'pjax-students',
        'enablePushState' => false,
        'timeout' => 10000,
    ]); ?>
        <?= $this->render('_students-list', [
            'dataProvider' => $dataProvider,
            'event' => $event
        ]) ?>
    <?php Pjax::end(); ?>
</div>
