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

$this->title = 'Студенты';
?>
<div class="site-students">
    <h1><?= Html::encode($this->title) ?></h1>
    
    <div>
        <?php Pjax::begin([
            'id' => 'ajax-form'
        ]); ?>
            <?php $form = ActiveForm::begin([
                'id' => 'add-student-form',
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
                        'class' => ActionColumn::className(),
                        'template' => '{delete}',
                        'urlCreator' => function ($action, $model, $key, $index, $column) {
                            return Url::toRoute(['/user/' . $action, 'id' => $model['id']]);
                        },
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
