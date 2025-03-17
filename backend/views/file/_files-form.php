<?php

use yii\bootstrap5\ActiveForm;
use yii\bootstrap5\Html;
use yii\widgets\Pjax;

/** @var yii\web\View $this */
/** @var yii\bootstrap5\ActiveForm $form */
/** @var app\models\FilesEvents $model */
?>

<div class="card">
    <div class="card-body">
        <?php $form = ActiveForm::begin([
            'id' => 'upload-file-form',
            'action' => ['/upload-files'],
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
            <div class="dropzone">
                <div class="dz-message needsclick">
                    <div class="mb-3">
                        <i class="display-4 text-muted ri-upload-cloud-2-fill"></i>
                    </div>
        
                    <h4>Перетащите файлы или нажмите, чтобы загрузить.</h4>
                </div>
    
                <div class="fallback">
                    <?= $form->field($model, 'files[]', [
                        'template' => "{label}\n{input}" . ($model->hasErrors('files') ? '' : "\n{error}"),
                    ])->fileInput(['multiple' => true, 'name' => 'files[]'])->label(false); ?>
                    <?php if ($model->hasErrors('files')): ?>
                        <div class="alert alert-danger">
                            <?php foreach ($model->getErrors('files') as $error): ?>
                                <div class="error-item"><?= $error ?></div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="d-flex mt-3">
                <?= Html::submitButton('
                    <span class="cnt-text">Сохранить файлы</span>
                    <span class="d-flex align-items-center d-none cnt-load">
                        <span class="spinner-border flex-shrink-0" role="status">
                            <span class="visually-hidden">Сохранение...</span>
                        </span>
                        <span class="flex-grow-1 ms-2">
                            Сохранение...
                        </span>
                    </span>
                ', ['class' => 'btn btn-success btn-load ms-auto btn-upload-file', 'name' => 'add']) ?>
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
                                    <strong class="error text-danger" data-dz-errormessage></strong>
                                    <div class="dz-progress progress progress-sm mt-1 d-none">
                                        <div class="dz-upload progress-bar animated-progress bg-success" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100" data-dz-uploadprogress></div>
                                    </div>
                                </div>
                            </div>
                            <div class="flex-shrink-0 ms-3 d-flex align-items-center">
                                <button data-dz-remove class="btn btn-icon btn-soft-danger btn-danger"><i class="ri-close-circle-line fs-18"></i></button>
                            </div>
                        </div>
                    </div>
                </li>
            </ul>
        <?php ActiveForm::end(); ?>
    </div>
</div>
