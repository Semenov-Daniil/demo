<?php

/** @var yii\web\View $this */
/** @var yii\bootstrap5\ActiveForm $form */

/** @var app\models\ExpertsCompetencies $model */
/** @var app\models\ExpertsCompetencies $dataProvider */

use app\widgets\Alert;
use yii\bootstrap5\ActiveForm;
use yii\grid\ActionColumn;
use yii\grid\GridView;
use yii\bootstrap5\Html;
use yii\widgets\Pjax;

$this->title = 'Эксперты';

$this->registerJsFile('/js/experts.js', ['depends' => 'yii\web\JqueryAsset']);
?>
<div class="site-experts">

    <h3><?= Html::encode($this->title) ?></h3>

    <div>
        <?php Pjax::begin([
            'id' => 'pjax-experts-form',
            'enablePushState' => false,
            'timeout' => 10000,
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
                            <?= $form->field($model, 'module_count')->textInput(['type' => 'number', 'min' => 1, 'value' => 1]) ?>
                        </div>
                    </div>
                </div>
                <div class="form-group">
                    <div>
                        <?= Html::submitButton('Добавить', ['class' => 'btn btn-success', 'name' => 'add']) ?>
                    </div>
                </div>
            <?php ActiveForm::end(); ?>
        <?php Pjax::end(); ?>

        <?php Pjax::begin([
            'id' => 'pjax-experts',
            'enablePushState' => false,
            'timeout' => 10000,
        ]); ?>
            <?= GridView::widget([
                'dataProvider' => $dataProvider,
                'pager' => ['class' => \yii\bootstrap5\LinkPager::class],
                'layout' => "
                    <div class=\"mt-3\">{pager}</div>\n
                    <div>{items}</div>\n
                    <div class=\"mt-3\">{pager}</div>",
                'columns' => [
                    [
                        'label' => 'Полное имя',
                        'value' => function ($model) {
                            return $model['fullName'];
                        },
                    ],
                    [
                        'label' => 'Логин/Пароль',
                        'value' => function ($model) {
                            return $model['loginPassword'];
                        },
                    ],
                    [
                        'label' => 'Компетенция',
                        'value' => function($model) {
                            return $model['title'];
                        },
                    ],
                    [
                        'label' => 'Кол-во модулей',
                        'value' => function($model) {
                            return $model['moduleCount'];
                        },
                    ],
                    [
                        'class' => ActionColumn::class,
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

