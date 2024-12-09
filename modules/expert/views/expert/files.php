<?php

/** @var yii\web\View $this */

/** @var app\models\FilesCompetencies $model */
/** @var app\models\FilesCompetencies $dataProvider */

use app\widgets\Alert;
use kartik\file\FileInput;
use yii\bootstrap5\ActiveForm;
use yii\bootstrap5\Html;
use yii\grid\ActionColumn;
use yii\grid\GridView;
use yii\widgets\Pjax;

$this->title = 'Файлы';

$this->registerJsFile('/js/files.js', ['depends' => 'yii\web\JqueryAsset']);
?>
<div class="site-files">
    <h3><?= Html::encode($this->title) ?></h3>
    <div>
        <?php Pjax::begin([
            'id' => 'pjax-files-form',
            'enablePushState' => false,
            'timeout' => 10000,
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
            
            <div>
                <?php
                    //echo '<label class="control-label">Add Attachments</label>';
                    //echo FileInput::widget([
                    //     'name' => 'attachment',
                    // ]);
                ?>
            </div>
        <?php Pjax::end(); ?>

        <?php Pjax::begin([
            'id' => 'pjax-files',
            'enablePushState' => false,
            'timeout' => 10000,
        ]); ?>
            <?= GridView::widget([
                'dataProvider' => $dataProvider,
                'pager' => ['class' => \yii\bootstrap5\LinkPager::class],
                'layout' => "
                    <div class=\"mt-3\">{pager}</div>\n
                    <div >{items}</div>\n
                    <div class=\"mt-3\">{pager}</div>",
                'columns' => [
                    [
                        'label' => 'Файл',
                        'format' => 'raw',
                        'value' => function ($model) {
                            return Html::a($model['originFullName'], ["/download/" . $model['dirTitle'] . "/" . $model['saveName']], ['data' => ['pjax' => '0']]);
                        },
                    ],
                    [
                        'class' => ActionColumn::class,
                        'template' => '{delete}',
                        'buttons' => [
                            'delete' => function ($url, $model, $key) {
                                return Html::button('Удалить', ['data' => ['id' => $model['fileId'], 'pjax' => true], 'class' => 'btn btn-danger btn-delete']);
                            }
                        ],
                    ],
                ],
            ]); ?>
        <?php Pjax::end(); ?>
    </div>
</div>
