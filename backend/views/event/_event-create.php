<?php

use yii\bootstrap5\ActiveForm;
use yii\bootstrap5\Html;

/** @var yii\web\View $this */
/** @var yii\bootstrap5\ActiveForm $form */
/** @var app\models\EventForm $model */
/** @var array $experts */

?>

<div class="card">
    <div class="card-header align-items-center d-flex">
        <h4 class="card-title mb-0 flex-grow-1">Добавление чемпионата</h4>
    </div>

    <div class="card-body">
        <?php $form = ActiveForm::begin([
            'id' => 'form-event-create',
            'action' => ['create-event'],
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

                <?php if (Yii::$app->user->can('sExpert')): ?>
                    <?= $form->field($model, 'expert', ['options' => ['class' => 'col-xl-5 mb-3 field-choices']])->dropDownList($experts, ['prompt' => 'Выберите эксперта',  'data' => ['choices' => true, 'choices-removeItem' => true]])?>
                <?php endif;?>

                <?= $form->field($model, 'title', ['options' => ['class' => 'col-md-8 mb-3' . (Yii::$app->user->can('sExpert') ? ' col-xl-4' : '')]])->textInput(['placeholder' => 'Введите название чемпионата']) ?>
            
                <?= $form->field($model, 'countModules', ['options' => ['class' => 'col-md-4 mb-3' . (Yii::$app->user->can('sExpert') ? ' col-xl-3' : '')]])->textInput(['type' => 'number', 'class' => 'form-control cleave-number', 'min' => 1, 'value' => ($model->countModules ? $model->countModules : 1), 'placeholder' => 'Введите кол-во модулей', 'data' => ['step' => true]]) ?>

                <div class="col-12 text-end">
                    <?= Html::submitButton('
                        <div class="d-flex align-items-center cnt-text"><i class="ri-add-line align-middle fs-16 me-2"></i> Добавить</div>
                        <div class="d-flex align-items-center d-none cnt-load">
                            <span class="spinner-border flex-shrink-0" role="status">
                            </span>
                            <span class="flex-grow-1 ms-2">
                                Добавление...
                            </span>
                        </div>
                    ', ['class' => 'btn btn-success btn-load ms-auto btn-create-event', 'name' => 'create']) ?>
                </div>
            </div>
        <?php ActiveForm::end(); ?>
    </div>
</div>

