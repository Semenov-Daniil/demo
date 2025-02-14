<?php

/** @var yii\web\View $this */
/** @var yii\bootstrap5\ActiveForm $form */

/** @var common\models\LoginForm $model */

use yii\bootstrap5\ActiveForm;
use yii\bootstrap5\Html;
use yii\widgets\Pjax;

$this->title = 'Авторизация';
?>

<div class="col-md-8 col-lg-6 col-xl-5">
    <div class="card mt-4 card-bg-fill">

        <div class="card-body p-4">
            <div class="text-center mt-2">
                <h5 class="text-primary"><?= Html::encode($this->title) ?></h5>
            </div>
            <?php Pjax::begin([
                'id' => 'pjax-login'
            ]); ?>
                <div class="p-2 mt-4">
                    <?php $form = ActiveForm::begin([
                        'id' => 'login-form',
                        'options' => [
                            'data' => ['pjax' => true]
                        ],
                        'fieldConfig' => [
                            'template' => "{label}\n{input}\n{error}",
                            'labelOptions' => ['class' => 'col-lg-12 col-form-label mr-lg-3'],
                            'inputOptions' => ['class' => 'col-lg-3 form-control'],
                            'errorOptions' => ['class' => 'col-lg-7 invalid-feedback'],
                        ],
                    ]); ?>

                    <?= $form->field($model, 'login')->textInput() ?>

                    <?= $form->field($model, 'password')->passwordInput() ?>

                    <div class="form-group">
                        <div>
                            <?= Html::submitButton('Войти в систему', ['class' => 'btn btn-success w-100', 'name' => 'login-button']) ?>
                        </div>
                    </div>

                    <?php ActiveForm::end(); ?>
                </div>
            <?php Pjax::end(); ?>
        </div>
        <!-- end card body -->
    </div>
    <!-- end card -->
</div>