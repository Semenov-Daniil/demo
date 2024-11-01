<?php

/** @var yii\web\View $this */

/** @var app\models\FilesCompetencies $model */

use app\widgets\Alert;
use kartik\file\FileInput;
use yii\bootstrap5\ActiveForm;
use yii\bootstrap5\Html;
use yii\widgets\Pjax;

$this->title = 'Файлы';
?>
<div class="site-files">
    <h3><?= Html::encode($this->title) ?></h3>
    <div>
        <?php Pjax::begin([
            'id' => 'pjax-files'
        ]); ?>

            <?= Alert::widget(); ?>

            <?php $form = ActiveForm::begin([
                'id' => 'add-files-form',
                'options' => [
                    'data' => ['pjax' => true],
                    'enctype' => 'multipart/form-data',
                ],
                'fieldConfig' => [
                    'template' => "{label}\n{input}\n{error}",
                    'labelOptions' => ['class' => 'col-form-label mr-lg-3'],
                    'inputOptions' => ['class' => 'form-control'],
                    'errorOptions' => ['class' => 'invalid-feedback'],
                ],
            ]); ?>
    
                <?= $form->field($model, 'files[]')->fileInput(['multiple' => true])->label(false); ?>
                
                <div class="form-group">
                    <div>
                        <?= Html::submitButton('Добавить', ['class' => 'btn btn-success', 'name' => 'add']) ?>
                    </div>
                </div>

            <?php ActiveForm::end(); ?>

        <?php Pjax::end(); ?>
    </div>
</div>
