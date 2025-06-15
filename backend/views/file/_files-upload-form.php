<?php

use yii\bootstrap5\ActiveForm;
use yii\bootstrap5\Html;
use yii\widgets\Pjax;

/** @var yii\web\View $this */
/** @var yii\bootstrap5\ActiveForm $form */
/** @var app\models\FilesEvents $model */
/** @var array $event */
/** @var array $directories */

?>

<?php $form = ActiveForm::begin([
    'id' => 'form-upload-files',
    'action' => ['upload-files'],
    'successCssClass' => '',
    'fieldConfig' => [
        'template' => "{label}\n{input}\n{error}",
        'labelOptions' => ['class' => 'col-12 col-form-label mr-lg-3'],
        'inputOptions' => ['class' => 'col-12 form-control'],
        'errorOptions' => ['class' => 'col-12 invalid-feedback'],
    ],
    'options' => [
        'data' => [
            'pjax' => true
        ]
    ],
]); ?>
    
    <div class="row mt-3">
        <?= $form->field($model, 'events_id', ['validateOnBlur' => false, 'validateOnChange' => false, 'options' => ['class' => 'col-12 col-md-6 mb-3 field-choices']])->dropDownList($events, ['id' => 'events-select', 'prompt' => 'Выберите событие', 'data' => ['choices' => true, 'choices-group' => true, 'choices-removeItem' => true], 'class' => 'form-select']) ?>
        <?= $form->field($model, 'modules_id', ['options' => ['class' => 'col-12 col-md-6 mb-3 field-choices']])->dropDownList($directories, ['id' => 'directories-select', 'prompt' => 'Выберите расположение', 'data' => ['choices' => true, 'choices-removeItem' => true, 'choices-search-false' => true, 'choices-sorting-false' => true], 'class' => 'form-select', 'value' => '0']) ?>
    </div>

    <div class="d-flex mt-2">
        <?= Html::submitButton('
            <span class="cnt-text">Сохранить файлы</span>
            <span class="d-flex align-items-center d-none cnt-load">
                <span class="spinner-border flex-shrink-0" role="status"></span>
                <span class="flex-grow-1 ms-2">
                    Сохранение...
                </span>
            </span>
        ', ['class' => 'btn btn-success btn-load ms-auto btn-upload-file', 'name' => 'add']) ?>
    </div>
<?php ActiveForm::end(); ?>
