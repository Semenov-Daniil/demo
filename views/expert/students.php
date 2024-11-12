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
use yii\helpers\Html;
use yii\helpers\Url;
use yii\helpers\VarDumper;
use yii\widgets\Pjax;

$this->title = 'Студенты';

$this->registerJsFile('/js/students.js', ['depends' => 'yii\web\JqueryAsset']);
?>
<div class="site-students">

    <h3><?= Html::encode($this->title) ?></h3>
    
    <div>
        <?php Pjax::begin([
            'id' => 'pjax-students-form',
            'enablePushState' => false,
            'timeout' => 10000,
        ]); ?>
            <?= Alert::widget(); ?>
            <?php $form = ActiveForm::begin([
                'id' => 'add-student-form',
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
    
                <div class="form-group">
                    <div>
                        <?= Html::submitButton('Добавить', ['class' => 'btn btn-success', 'name' => 'add']) ?>
                    </div>
                </div>
            <?php ActiveForm::end(); ?>
        <?php Pjax::end(); ?>

        <?php Pjax::begin([
            'id' => 'pjax-students',
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
                        'class' => ActionColumn::class,
                        'template' => '{delete}',
                        'buttons' => [
                            'delete' => function ($url, $model, $key) {
                                return Html::button('Удалить', ['data' => ['id' => $model['students_id'], 'pjax' => true], 'class' => 'btn btn-danger btn-delete']);
                            }
                        ],
                        'visibleButtons' => [
                            'delete' => function ($model, $key, $index) {
                                return Yii::$app->user->can('expert');
                            }
                        ]
                    ],
                ],
            ]); ?>
        <?php Pjax::end(); ?>
    </div>
</div>
