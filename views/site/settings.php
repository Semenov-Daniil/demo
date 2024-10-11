<?php

/** @var yii\web\View $this */
/** @var yii\bootstrap5\ActiveForm $form */

/** @var app\models\Users $user */
/** @var app\models\Testings $testing */
/** @var app\models\Users $dataProvider */

use app\models\Users;
use yii\bootstrap5\ActiveForm;
use yii\grid\ActionColumn;
use yii\grid\GridView;
use yii\helpers\Html;
use yii\helpers\Url;
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
    
            <?= $form->field($user, 'surname')->textInput(['autofocus' => true]) ?>
    
            <?= $form->field($user, 'name')->textInput() ?>
            
            <?= $form->field($user, 'middle_name')->textInput() ?>
    
            <?= $form->field($testing, 'title')->textInput() ?>
            
            <?= $form->field($testing, 'num_modules')->textInput(['type' => 'number', 'min' => 1, 'value' => 1]) ?>
    
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
                        'value' => function ($data) {
                            return trim($data->surname) . ' ' . trim($data->name) . ($data->middle_name ? (' ' . trim($data->middle_name)) : '');
                        },
                    ],

                    [
                        'label' => 'Login/Password',
                        'value' => function ($data) {
                            return $data->login . '/' . $data->password;
                        },
                    ],

                    [
                        'class' => ActionColumn::className(),
                        'urlCreator' => function ($action, Users $model, $key, $index, $column) {
                            return Url::toRoute([$action, 'id' => $model->id]);
                        },
                        'buttons' => ['{delete}'],
                        'visibleButtons' => [
                            'delete' => function ($model, $key, $index) {
                                return Yii::$app->user->can('expert') && Yii::$app->user->id !== $model->id;
                            }
                        ]
                    ],
                ],
            ]); ?>
        <?php Pjax::end(); ?>
    </div>
</div>
