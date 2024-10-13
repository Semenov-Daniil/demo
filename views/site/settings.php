<?php

/** @var yii\web\View $this */
/** @var yii\bootstrap5\ActiveForm $form */

/** @var app\models\UsersCompetencies $model */
/** @var app\models\UsersCompetencies $dataProvider */

use app\models\Users;
use yii\bootstrap5\ActiveForm;
use yii\grid\ActionColumn;
use yii\grid\GridView;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\helpers\VarDumper;
use yii\widgets\Pjax;

$this->title = 'Настройки';
?>
<div class="site-settings">
    <h1><?= Html::encode($this->title) ?></h1>
    
    <div>
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
    
            <?= $form->field($model, 'surname')->textInput(['autofocus' => true]) ?>
    
            <?= $form->field($model, 'name')->textInput() ?>
            
            <?= $form->field($model, 'middle_name')->textInput() ?>
    
            <?= $form->field($model, 'title')->textInput() ?>
            
            <?= $form->field($model, 'num_modules')->textInput(['type' => 'number', 'min' => 1, 'value' => 1]) ?>
    
            <div class="form-group">
                <div>
                    <?= Html::submitButton('Добавить', ['class' => 'btn btn-success', 'name' => 'add-button']) ?>
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
                        'attribute' => 'Test',
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
                    // [
                    //     'class' => ActionColumn::className(),
                    //     'template' => '{delete}',
                    //     'urlCreator' => function ($action, $model, $key, $index, $column) {
                    //         return Url::toRoute([$action, 'id' => $model['id']]);
                    //     },
                    //     'visibleButtons' => [
                    //         'delete' => function ($model, $key, $index) {
                    //             return Yii::$app->user->can('expert') && Yii::$app->user->id !== $model['id'];
                    //         }
                    //     ]
                    // ],
                ],
            ]); ?>
        <?php Pjax::end(); ?>
    </div>
</div>
