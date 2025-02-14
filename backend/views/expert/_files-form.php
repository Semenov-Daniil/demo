<?php

use yii\bootstrap5\ActiveForm;
use yii\bootstrap5\Html;

/** @var yii\web\View $this */
/** @var yii\bootstrap5\ActiveForm $form */
/** @var app\models\FilesEvents $model */
?>

<div class="files-from my-5">
    <?php $form = ActiveForm::begin([
        'id' => 'add-files-form',
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
        
        <?= $form->field($model, 'files[]')->fileInput(['multiple' => true])->label(false); ?>
        
        <div class="form-group">
            <div>
                <?= Html::submitButton('Добавить', ['class' => 'btn btn-success', 'name' => 'add']) ?>
            </div>
        </div>
    <?php ActiveForm::end(); ?>
</div>

