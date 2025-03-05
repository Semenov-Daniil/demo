<?php

use yii\bootstrap5\ActiveForm;
use yii\bootstrap5\Html;

/** @var yii\web\View $this */
/** @var yii\bootstrap5\ActiveForm $form */
/** @var app\models\Experts $model */
?>

<div class="card">
    <div class="card-header align-items-center d-flex">
        <h4 class="card-title mb-0 flex-grow-1">Добавление эксперта</h4>
    </div>

    <div class="card-body">
        <?php $form = ActiveForm::begin([
            'id' => 'add-expert-form',
            'action' => ['create-expert'],
            'options' => [
                'data' => [
                    'pjax' => true
                ],
            ],
            'fieldConfig' => [
                'template' => "{label}\n{input}\n{error}",
                'labelOptions' => ['class' => 'col-12 col-form-label mr-lg-3'],
                'inputOptions' => ['class' => 'form-control'],
                'errorOptions' => ['class' => 'col-12 invalid-feedback'],
            ],
        ]); ?>

            <div class="row">
                <?= $form->field($model, 'surname', ['options' => ['class' => 'col-lg-4 mb-3']])->textInput() ?>
        
                <?= $form->field($model, 'name', ['options' => ['class' => 'col-lg-4 mb-3']])->textInput() ?>

                <?= $form->field($model, 'patronymic', ['options' => ['class' => 'col-lg-4 mb-3']])->textInput() ?>

                <div class="col-12 text-end">
                    <?= Html::submitButton('
                        <span class="cnt-text"><i class="ri-add-line align-middle fs-16 me-2"></i> Добавить</span>
                        <span class="d-flex align-items-center d-none cnt-load">
                            <span class="spinner-border flex-shrink-0" role="status">
                            </span>
                            <span class="flex-grow-1 ms-2">
                                Добавление...
                            </span>
                        </span>
                    ', ['class' => 'btn btn-success btn-load ms-auto btn-add-expert', 'name' => 'add']) ?>
                </div>
            </div>
        <?php ActiveForm::end(); ?>
    </div>
</div>

