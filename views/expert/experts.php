<?php

/** @var yii\web\View $this */
/** @var yii\bootstrap5\ActiveForm $form */

/** @var app\models\UsersCompetencies $model */
/** @var app\models\UsersCompetencies $dataProvider */

use app\models\Users;
use app\widgets\Alert;
use yii\bootstrap5\ActiveForm;
use yii\grid\ActionColumn;
use yii\grid\GridView;
use yii\bootstrap5\Html;
use yii\helpers\Url;
use yii\helpers\VarDumper;
use yii\widgets\Pjax;

$this->title = 'Эксперты';

$deleteExpert = <<<JS
    $("#pjax-experts").on("mousedown", ".btn-delete", function(event_mousedown) {
        event_mousedown.preventDefault();
        $(this).on("mouseup", function(event_mouseup) {
            event_mouseup.preventDefault();
            $.ajax({
                type: "DELETE",
                url: "/experts/" + event_mouseup.target.dataset.id,
                success: function (response) {
                    $.pjax.reload({container: "#pjax-experts"});
                },
            });
        });
    });
JS;

$this->registerJs($deleteExpert);
?>
<div class="site-experts">
    <h3><?= Html::encode($this->title) ?></h3>
    <div>
        <?php Pjax::begin([
            'id' => 'pjax-experts'
        ]); ?>
            <?= Alert::widget(); ?>
            <?php $form = ActiveForm::begin([
                'id' => 'add-expert-form',
                'options' => [
                    'data' => ['pjax' => true]
                ],
                'fieldConfig' => [
                    'template' => "{label}\n{input}\n{error}",
                    'labelOptions' => ['class' => 'col-form-label mr-lg-3'],
                    'inputOptions' => ['class' => 'form-control'],
                    'errorOptions' => ['class' => 'invalid-feedback'],
                ],
            ]); ?>
    
                <div>
                    <div class="row">
                        <div class="col-4">
                            <?= $form->field($model, 'surname')->textInput() ?>
                        </div>
                        <div class="col-4">
                            <?= $form->field($model, 'name')->textInput() ?>
                        </div>
                        <div class="col-4">
                            <?= $form->field($model, 'middle_name')->textInput() ?>
                        </div>
                    </div>
                </div>
                <div>
                    <div class="row">
                        <div class="col-6">
                            <?= $form->field($model, 'title')->textInput() ?>
                        </div>
                        <div class="col-6">
                            <?= $form->field($model, 'num_modules')->textInput(['type' => 'number', 'min' => 1, 'value' => 1]) ?>
                        </div>
                    </div>
                </div>
                <div class="form-group">
                    <div>
                        <?= Html::submitButton('Добавить', ['class' => 'btn btn-success', 'name' => 'add']) ?>
                    </div>
                </div>
            <?php ActiveForm::end(); ?>
            <?= GridView::widget([
                'dataProvider' => $dataProvider,
                'pager' => ['class' => \yii\bootstrap5\LinkPager::class],
                'columns' => [
                    [
                        'label' => 'Full name',
                        'value' => function ($model) {
                            return trim($model['surname']) . ' ' . trim($model['name']) . ($model['middle_name'] ? (' ' . trim($model['middle_name'])) : '');
                        },
                    ],
                    [
                        'label' => 'Login/Password',
                        'value' => function ($model) {
                            return $model['login'] . '/' . $model['password'];
                        },
                    ],
                    [
                        'attribute' => 'Competencies',
                        'value' => function($model) {
                            return $model['title'];
                        },
                    ],
                    [
                        'attribute' => 'Num modules',
                        'value' => function($model) {
                            return $model['num_modules'];
                        },
                    ],
                    [
                        'class' => ActionColumn::className(),
                        'template' => '{delete}',
                        'buttons' => [
                            'delete' => function ($url, $model, $key) {
                                return Html::button('Удалить', ['data' => ['id' => $model['id'], 'pjax' => true], 'class' => 'btn btn-danger btn-delete']);
                            }
                        ],
                        'visibleButtons' => [
                            'delete' => function ($model, $key, $index) {
                                return Yii::$app->user->id !== $model['id'];
                            }
                        ]
                    ],
                ],
            ]); ?>
        <?php Pjax::end(); ?>


    </div>
</div>

