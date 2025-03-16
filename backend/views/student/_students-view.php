<?php

/** @var yii\web\View $this */

/** @var app\models\StudentsEvents $model */
/** @var app\models\StudentsEvents $dataProvider */
/** @var string $event */

use common\assets\AppAsset;
use yii\widgets\Pjax;

?>

<div class="event-info mb-3">
    <h5><?= $model->event?->expert->fullName ?>: <?= $model->event?->title ?></h5>
</div>

<div class="row">
    <?php Pjax::begin([
        'id' => 'pjax-create-student',
        'enablePushState' => false,
        'timeout' => 10000,
    ]); ?>
        <?= $this->render('_student-create', [
            'model' => $model,
        ]) ?>
    <?php Pjax::end(); ?>
</div>

<div class="row">
    <?php Pjax::begin([
        'id' => 'pjax-students',
        'enablePushState' => false,
        'timeout' => 10000,
        'options' => [
            'data' => [
                'pjax-grid' => true
            ]
        ]
    ]); ?>
        <?= $this->render('_students-list', [
            'dataProvider' => $dataProvider,
            'event' => $event
        ]) ?>
    <?php Pjax::end(); ?>
</div>
