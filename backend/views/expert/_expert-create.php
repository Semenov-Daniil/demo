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
            'id' => 'form-create-expert',
            'action' => ['/expert/create-expert'],
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

                <?= $form->field($model, 'surname', ['options' => ['class' => 'col-lg-4 mb-3']])->textInput(['placeholder' => 'Введите фамилию']) ?>
        
                <?= $form->field($model, 'name', ['options' => ['class' => 'col-lg-4 mb-3']])->textInput(['placeholder' => 'Введите имя']) ?>

                <?= $form->field($model, 'patronymic', ['options' => ['class' => 'col-lg-4 mb-3']])->textInput(['placeholder' => 'Введите отчество']) ?>

                <div class="col-12 d-flex flex-wrap gap-2 justify-content-end">
                    <?= Html::submitButton('
                        <div class="d-flex align-items-center cnt-text"><i class="ri-add-line align-middle fs-16 me-2"></i> Добавить</div>
                        <div class="d-flex align-items-center d-none cnt-load">
                            <span class="spinner-border flex-shrink-0" role="status">
                            </span>
                            <span class="flex-grow-1 ms-2">
                                Добавление...
                            </span>
                        </div>
                    ', ['class' => 'btn btn-success btn-load btn-create-expert', 'name' => 'add']) ?>
                </div>
            </div>
        <?php ActiveForm::end(); ?>
    </div>
</div>

