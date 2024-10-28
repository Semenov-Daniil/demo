<?php

/** @var yii\web\View $this */
/** @var yii\bootstrap5\ActiveForm $form */

/** @var app\models\LoginForm $model */
/** @var app\models\Users $users */

use yii\bootstrap5\ActiveForm;
use yii\bootstrap5\Html;
use yii\widgets\Pjax;

$this->title = 'Авторизация';
?>
<div class="user-login">
    <h1><?= Html::encode($this->title) ?></h1>

    <?php Pjax::begin([
        'id' => 'pjax-login'
    ]); ?>
        <?php $form = ActiveForm::begin([
            'id' => 'login-form',
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

        <?= $form->field($model, 'login')->textInput() ?>

        <?= $form->field($model, 'password')->passwordInput() ?>

        <div class="form-group">
            <div>
                <?= Html::submitButton('Войти в систему', ['class' => 'btn btn-success', 'name' => 'login-button']) ?>
            </div>
        </div>

        <?php ActiveForm::end(); ?>
    <?php Pjax::end(); ?>

    <div style="color:#999;">
        You may login with <br>
        expert: <strong><?= $users['expert']?->login ?>/<?= $users['expert']?->password ?></strong><br>
        student: <strong><?= $users['student']?->login ?>/<?= $users['student']?->password ?></strong>.
    </div>

</div>