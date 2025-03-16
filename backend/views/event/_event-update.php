<?php

use yii\bootstrap5\ActiveForm;
use yii\bootstrap5\Html;
use yii\widgets\Pjax;

/** @var yii\web\View $this */
/** @var yii\bootstrap5\ActiveForm $form */
/** @var app\models\Events $model */
/** @var array $experts */

?>

<?php Pjax::begin([
    'id' => 'pjax-update-event',
    'enablePushState' => false,
    'timeout' => 10000
]); ?> 

    <?php $form = ActiveForm::begin([
        'id' => 'form-update-event',
        'options' => [
            'data' => [
                'pjax' => true
            ],
        ],
        'fieldConfig' => [
            'template' => "{label}\n{input}\n{error}",
            'labelOptions' => ['class' => 'col-12 col-form-label mr-lg-3 pt-0'],
            'inputOptions' => ['class' => 'form-control col-12'],
            'errorOptions' => ['class' => 'col-12 invalid-feedback'],
        ],
    ]); ?>
        <div class="row">

            <?php if (Yii::$app->user->can('sExpert')): ?>
                <?= $form->field($model, 'experts_id', ['options' => ['class' => 'mb-3 field-choices']])->dropDownList($experts, ['prompt' => 'Выберите эксперта',  'data' => ['choices' => true, 'choices-removeItem' => true]])?>
            <?php endif;?>

            <?= $form->field($model, 'title')->textInput(['placeholder' => 'Введите название чемпионата']) ?>

            <div class="col-12 d-flex flex-wrap gap-2 justify-content-end">
                <?= Html::button('Назад', ['class' => 'btn btn-info', 'data' => ['bs-dismiss' => 'modal']])?>
                <?= Html::submitButton('
                    <span class="cnt-text d-flex align-items-center">Обновить</span>
                    <span class="d-flex align-items-center d-none cnt-load">
                        <span class="spinner-border flex-shrink-0" role="status">
                        </span>
                        <span class="flex-grow-1 ms-2">
                            Обновление...
                        </span>
                    </span>
                ', ['class' => 'btn btn-success btn-load btn-update-event', 'name' => 'create', 'data' => ['id' => $model->id]]) ?>
            </div>
        </div>
    <?php ActiveForm::end(); ?>

<?php Pjax::end(); ?>
