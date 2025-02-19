<?php

use yii\bootstrap5\ActiveForm;
use yii\bootstrap5\Html;

/** @var yii\web\View $this */
/** @var yii\bootstrap5\ActiveForm $form */
/** @var app\models\FilesEvents $model */
?>

<!-- <div class="progress progress-sm">
    <div class="progress-bar bg-success" role="progressbar" style="width: 50%" aria-valuenow="50" aria-valuemin="0" aria-valuemax="100"></div>
</div> -->

<div class="card">
    <div class="card-body">
        <?php $form = ActiveForm::begin([
            'id' => 'upload-form',
            'options' => [
                'class' => 'dropzone',
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

            <div class="fallback">
                <?= $form->field($model, 'files[]')->fileInput(['multiple' => true, 'name' => 'files[]'])->label(false); ?>
            </div>

            <div class="dz-message needsclick">
                <div class="mb-3">
                    <i class="display-4 text-muted ri-upload-cloud-2-fill"></i>
                </div>
    
                <h4>Перетащите файлы или нажмите, чтобы загрузить.</h4>
            </div>
            
        <?php ActiveForm::end(); ?>

        <div class="d-flex mt-3">
            <?= Html::button('Загрузить файл', ['class' => 'btn btn-success ms-auto btn-upload-file', 'name' => 'add']) ?>
        </div>

        <ul class="list-unstyled mb-0" id="dropzone-preview">
            <li class="mt-2 dz-preview-item d-none" id="dropzone-preview-list">
                <div class="border rounded">
                    <div class="d-flex p-2">
                        <div class="d-flex flex-shrink-0 align-items-center me-3">
                            <div class="d-flex align-items-center justify-content-center avatar-sm preview-img bg-light rounded">
                                <i class="display-6 text-muted ri-file-3-fill"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1">
                            <div class="pt-1">
                                <h5 class="fs-14 mb-1" data-dz-name>&nbsp;</h5>
                                <p class="fs-13 text-muted mb-0" data-dz-size></p>
                                <div class="dz-progress progress progress-sm mt-1">
                                    <div class="dz-upload progress-bar animated-progress bg-success" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100" data-dz-uploadprogress></div>
                                </div>
                                <strong class="error text-danger" data-dz-errormessage></strong>
                            </div>
                        </div>
                        <div class="flex-shrink-0 ms-3">
                            <button data-dz-remove class="btn btn-sm btn-danger">Удалить</button>
                        </div>
                    </div>
                </div>
            </li>
        </ul>
    </div>
</div>



