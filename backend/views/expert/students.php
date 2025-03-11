<?php

/** @var yii\web\View $this */
/** @var yii\bootstrap5\ActiveForm $form */

/** @var app\models\StudentsEvents $model */
/** @var app\models\StudentsEvents $dataProvider */
/** @var array $events */

use common\assets\AppAsset;
use common\assets\ChoicesAsset;
use common\models\Events;
use common\widgets\Alert;
use yii\helpers\Html;
use yii\web\YiiAsset;
use yii\widgets\Pjax;

$this->title = 'Студенты';

ChoicesAsset::register($this);

$this->registerJsFile('@web/js/students.js', ['depends' => AppAsset::class]);

?>

<div class="row mb-3">
    <div>
        <label for="events-select" class="form-label text-muted col-12">Чемпионаты</label>
        <?= Html::dropDownList('events', false, $events, [
            'id' => 'events-select',
            'prompt' => 'Выберите чемпионат',
            'data' => [
                'choices' => true,
                'choices-group' => true,
                'choices-removeItem' => true,
            ],
            'class' => 'form-select'
        ]); ?>
    </div>
</div>

<div id="students-wrap">
    <?= $this->render('_students-not-view'); ?>
</div>