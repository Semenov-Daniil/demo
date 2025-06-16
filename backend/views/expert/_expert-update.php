<?php

use yii\bootstrap5\ActiveForm;
use yii\bootstrap5\Html;
use yii\widgets\Pjax;

/** @var yii\web\View $this */
/** @var yii\bootstrap5\ActiveForm $form */
/** @var app\models\Experts $model */
?>

<?php Pjax::begin([
    'id' => 'pjax-update-expert',
    'enablePushState' => false,
    'timeout' => 10000
]); ?>
    <?php $form = ActiveForm::begin([
        'id' => 'form-update-expert',
        'options' => [
            'data' => [
                'pjax' => true
            ],
        ],
        'fieldConfig' => [
            'template' => "{label}\n{input}\n{error}",
            'labelOptions' => ['class' => 'col-12 col-form-label mr-lg-3 pt-0'],
            'inputOptions' => ['class' => 'form-control'],
            'errorOptions' => ['class' => 'col-12 invalid-feedback'],
        ],
    ]); ?>

        <div class="row">

            <?= $form->field($model, 'surname')->textInput(['placeholder' => 'Введите фамилию']) ?>

            <?= $form->field($model, 'name')->textInput(['placeholder' => 'Введите имя']) ?>

            <?= $form->field($model, 'patronymic')->textInput(['placeholder' => 'Введите отчество']) ?>

            <?= Html::hiddenInput(Html::getInputName($model, 'updated_at'), $model->updated_at, ['id' => Html::getInputId($model, 'updated_at')])?>

            <div class="col-12 d-flex flex-wrap gap-2 justify-content-end">
                <?= Html::submitButton('
                    <div class="cnt-text d-flex align-items-center">Сохранить</div>
                    <div class="d-flex align-items-center d-none cnt-load">
                        <span class="spinner-border flex-shrink-0" role="status"></span>
                        <span class="flex-grow-1 ms-2">
                            Сохранение...
                        </span>
                    </div>
                ', ['class' => 'btn btn-success btn-load btn-update-expert', 'name' => 'update']) ?>
            </div>
        </div>
    <?php ActiveForm::end(); ?>
<?php Pjax::end(); ?>

