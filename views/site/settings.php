<?php

/** @var yii\web\View $this */
/** @var yii\bootstrap5\ActiveForm $form */

/** @var app\models\Users $user */

use yii\bootstrap5\ActiveForm;
use yii\helpers\Html;
use yii\widgets\Pjax;

$this->title = 'Настройки';
?>
<div class="site-settings">
    <h1><?= Html::encode($this->title) ?></h1>
    
    <?php Pjax::begin([
        'id' => 'ajax-form'
    ]); ?>
        <?php $form = ActiveForm::begin([
            'id' => 'add-expert-form',
            'options' => [
                'data' => ['pjax' => true]
            ],
            'fieldConfig' => [
                'template' => "{label}\n{input}\n{error}",
                'labelOptions' => ['class' => 'col-lg-1 col-form-label mr-lg-3'],
                'inputOptions' => ['class' => 'col-lg-3 form-control'],
                'errorOptions' => ['class' => 'col-lg-7 invalid-feedback'],
            ],
        ]); ?>

        <?= $form->field($user, 'surname')->textInput(['autofocus' => true]) ?>

        <?= $form->field($user, 'name')->passwordInput() ?>
        
        <?= $form->field($user, 'middle_name')->textInput() ?>

        <?= $form->field($champ, 'title')->textInput() ?>
        
        <?= $form->field($champ, 'num_modules')->textInput(['type' => 'number', 'min' => 1]) ?>

        <div class="form-group">
            <div>
                <?= Html::submitButton('Добавить', ['class' => 'btn btn-success', 'name' => 'add-button']) ?>
            </div>
        </div>

        <?php ActiveForm::end(); ?>
    <?php Pjax::end(); ?>
</div>
