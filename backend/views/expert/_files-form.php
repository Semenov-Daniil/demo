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

<!-- <div class="card">
    <div class="card-body">
        <p class="text-muted">DropzoneJS is an open source library that provides drag’n’drop file uploads with image previews.</p>

        <div class="dropzone dz-clickable">
            
            <div class="dz-message needsclick">
                <div class="mb-3">
                    <i class="display-4 text-muted ri-upload-cloud-2-fill"></i>
                </div>

                <h4>Drop files here or click to upload.</h4>
            </div>
        </div>

        <ul class="list-unstyled mb-0" id="dropzone-preview">
            
        <li class="mt-2 dz-processing dz-success dz-complete" id=""> 
    </div>
</div> -->

