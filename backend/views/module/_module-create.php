<?php

use backend\assets\AppAsset as BackendAppAsset;
use common\assets\ChoicesAsset;
use yii\bootstrap5\ActiveForm;
use yii\bootstrap5\Html;

/** @var yii\web\View $this */
/** @var yii\bootstrap5\ActiveForm $form */
/** @var app\models\Module $model */
/** @var array $events */

$this->registerJsFile('@web/js/modules/module/moduleCreate.js', ['depends' => [BackendAppAsset::class, ChoicesAsset::class]], 'moduleCreate');

?>

<?php $form = ActiveForm::begin([
    'id' => 'form-create-module',
    'action' => ['create-module'],
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
    ],
]); ?>
    <div class="row">
        <?= $form->field($model, 'events_id', ['validateOnBlur' => false, 'validateOnChange' => false, 'options' => ['class' => 'col-12 field-choices']])->dropDownList($events, ['id' => 'events-select', 'prompt' => 'Выберите чемпионат', 'data' => ['choices' => true, 'choices-group' => true, 'choices-removeItem' => true], 'class' => 'form-select']) ?>
    </div>
<?php ActiveForm::end(); ?>

