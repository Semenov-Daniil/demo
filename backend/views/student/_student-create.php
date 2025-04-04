<?php

use common\assets\ChoicesAsset;
use yii\bootstrap5\ActiveForm;
use yii\bootstrap5\Html;
use yii\web\YiiAsset;

/** @var yii\web\View $this */
/** @var yii\bootstrap5\ActiveForm $form */
/** @var app\models\StudentsEvents $model */

$this->registerJsFile('@web/js/modules/student/studentCreate.js', ['depends' => [YiiAsset::class, ChoicesAsset::class]], 'studentCreate');

?>

<div class="card">
    <div class="card-header align-items-center d-flex">
        <h4 class="card-title mb-0 flex-grow-1">Добавление студента</h4>
    </div>

    <div class="card-body">
        <?php $form = ActiveForm::begin([
            'id' => 'add-student-form',
            'action' => ['/create-student'],
            'fieldConfig' => [
                'template' => "{label}\n{input}\n{error}",
                'labelOptions' => ['class' => 'col-12 col-form-label mr-lg-3 pt-0'],
                'inputOptions' => ['class' => 'form-control'],
                'errorOptions' => ['class' => 'col-12 invalid-feedback'],
            ],
            'options' => [
                'data' => [
                    'pjax' => true
                ]
            ]
        ]); ?>
            <div class="row">
                <?= $form->field($model, 'events_id', ['validateOnBlur' => false, 'validateOnChange' => false, 'options' => ['class' => 'col-12 mb-3 field-choices']])->dropDownList($events, ['id' => 'events-select', 'prompt' => 'Выберите чемпионат', 'data' => ['choices' => true, 'choices-group' => true, 'choices-removeItem' => true], 'class' => 'form-select']) ?>
                
                <?= $form->field($model, 'surname', ['options' => ['class' => 'col-lg-4 mb-3']])->textInput(['placeholder' => 'Введите фамилию']) ?>

                <?= $form->field($model, 'name', ['options' => ['class' => 'col-lg-4 mb-3']])->textInput(['placeholder' => 'Введите имя']) ?>

                <?= $form->field($model, 'patronymic', ['options' => ['class' => 'col-lg-4 mb-3']])->textInput(['placeholder' => 'Введите отчество']) ?>

                <div class="col-12 text-end">
                    <?= Html::submitButton('
                        <span class="d-flex align-items-center cnt-text"><i class="ri-add-line align-middle fs-16 me-2"></i> Добавить</span>
                        <span class="d-flex align-items-center cnt-load d-none">
                            <span class="spinner-border flex-shrink-0" role="status">
                            </span>
                            <span class="flex-grow-1 ms-2">
                                Добавление...
                            </span>
                        </span>
                    ', ['class' => 'btn btn-success btn-load btn-create-student', 'name' => 'add']) ?>
                </div>
            </div>
        <?php ActiveForm::end(); ?>
    </div>
</div>

