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

$deleteFile = <<<JS
    $("#pjax-files").on("mousedown", ".btn-delete", function(event_mousedown) {
        event_mousedown.preventDefault();
        $(this).on("mouseup", function(event_mouseup) {
            event_mouseup.preventDefault();
            $.ajax({
                type: "DELETE",
                url: "/files/" + event_mouseup.target.dataset.id,
                success: function (response) {
                    $.pjax.reload({container: "#pjax-files"});
                },
            });
        });
    });
JS;

$this->registerJs($deleteFile, $this::POS_READY);
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
            
            <div>
                <?php
                    //echo '<label class="control-label">Add Attachments</label>';
                    //echo FileInput::widget([
                    //     'name' => 'attachment',
                    // ]);
                ?>
            </div>

            <?= GridView::widget([
                'dataProvider' => $dataProvider,
                'columns' => [
                    [
                        'value' => function ($model) {
                            return Html::a($model['originFullName'], ["/download/" . $model['dirTitle'] . "/" . $model['saveName']], ['data' => ['pjax' => '0']]);
                        },
                        'format' => 'raw',
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
