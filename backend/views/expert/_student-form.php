<?php

use yii\bootstrap5\ActiveForm;
use yii\bootstrap5\Html;

/** @var yii\web\View $this */
/** @var yii\bootstrap5\ActiveForm $form */
/** @var app\models\StudentsEvents $model */
?>

<div class="card student-from">
    <div class="card-body">
        <?php $form = ActiveForm::begin([
            'id' => 'add-student-form',
            'options' => [
                'data' => [
                    'pjax' => true
                ]
            ],
            'fieldConfig' => [
                'template' => "{label}\n{input}\n{error}",
                'labelOptions' => ['class' => 'col-form-label mr-lg-3'],
                'inputOptions' => ['class' => 'form-control'],
                'errorOptions' => ['class' => 'invalid-feedback'],
            ],
        ]); ?>
            <div class="row g-3">
                <div class="col-4">
                    <?= $form->field($model, 'surname')->textInput() ?>
                </div>
                <div class="col-4">
                    <?= $form->field($model, 'name')->textInput() ?>
                </div>
                <div class="col-4">
                    <?= $form->field($model, 'patronymic')->textInput() ?>
                </div>
            </div>
            
            <div class="form-group">
                <div>
                    <?= Html::submitButton('Добавить', ['class' => 'btn btn-success', 'name' => 'add']) ?>
                </div>
            </div>
        <?php ActiveForm::end(); ?>
    </div>
</div>

