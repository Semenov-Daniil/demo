<?php

use yii\bootstrap5\ActiveForm;
use yii\bootstrap5\Html;

/** @var yii\web\View $this */
/** @var yii\bootstrap5\ActiveForm $form */
/** @var app\models\ExpertsEvents $model */
?>

<div class="card expert-from">
    <div class="card-body">
        <?php $form = ActiveForm::begin([
            'id' => 'add-expert-form',
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
                <?= $form->field($model, 'surname', ['options' => ['class' => 'col-12 col-md-4']])->textInput() ?>
        
                <?= $form->field($model, 'name', ['options' => ['class' => 'col-12 col-md-4']])->textInput() ?>
        
                <?= $form->field($model, 'patronymic', ['options' => ['class' => 'col-12 col-md-4']])->textInput() ?>
            </div>
            <div class="row g-3 mt-0">
                <?= $form->field($model, 'title', ['options' => ['class' => 'col-12 col-md-6']])->textInput() ?>
        
                <?= $form->field($model, 'countModules', ['options' => ['class' => 'col-12 col-md-6']])->textInput(['type' => 'number', 'min' => 1, 'value' => 1]) ?>
            </div>
            
            <div class="form-group my-3">
                <div>
                    <?= Html::submitButton('Добавить', ['class' => 'btn btn-success', 'name' => 'add']) ?>
                </div>
            </div>
        <?php ActiveForm::end(); ?>
    </div>
</div>

