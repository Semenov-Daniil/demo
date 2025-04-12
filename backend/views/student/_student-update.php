<?php

use yii\bootstrap5\ActiveForm;
use yii\bootstrap5\Html;
use yii\widgets\Pjax;

/** @var yii\web\View $this */
/** @var yii\bootstrap5\ActiveForm $form */
/** @var app\models\StudentsEvents $model */

?>

<?php Pjax::begin([
    'id' => 'pjax-update-student',
    'enablePushState' => false,
    'timeout' => 10000
])?>
    <?php $form = ActiveForm::begin([
        'id' => 'form-update-student',
        'fieldConfig' => [
            'template' => "{label}\n{input}\n{error}",
            'labelOptions' => ['class' => 'col-12 col-form-label mr-lg-3 pt-0'],
            'inputOptions' => ['class' => 'form-control'],
            'errorOptions' => ['class' => 'col-12 invalid-feedback'],
        ],
        'options' => [
            'data' => [
                'pjax' => true
            ]
        ]
    ]); ?>
        <div class="row">
            <?= $form->field($model, 'surname')->textInput(['placeholder' => 'Введите фамилию']) ?>

            <?= $form->field($model, 'name')->textInput(['placeholder' => 'Введите имя']) ?>

            <?= $form->field($model, 'patronymic')->textInput(['placeholder' => 'Введите отчество']) ?>

            <div class="col-12 text-end">
                <?= Html::submitButton('
                    <span class="d-flex align-items-center cnt-text">Сохранить</span>
                    <span class="d-flex align-items-center cnt-load d-none">
                        <span class="spinner-border flex-shrink-0" role="status">
                        </span>
                        <span class="flex-grow-1 ms-2">
                            Сохранение...
                        </span>
                    </span>
                ', ['class' => 'btn btn-success btn-load btn-update-student', 'name' => 'update']) ?>
            </div>
        </div>
    <?php ActiveForm::end(); ?>
<?php Pjax::end(); ?>
