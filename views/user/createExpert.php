<?php

/** @var yii\web\View $this */
/** @var yii\bootstrap5\ActiveForm $form */

/** @var app\models\Users $model */
/** @var app\models\Users $dataProvider */

use app\models\Users;
use yii\bootstrap5\ActiveForm;
use yii\bootstrap5\Html;
use yii\helpers\Url;
use yii\grid\ActionColumn;
use yii\grid\GridView;

$this->title = 'Главная';
?>
<div class="create-expert">
    <h1><?= Html::encode($this->title) ?></h1>

    <div>
        <?php $form = ActiveForm::begin([
            'id' => 'create-expert-form',
            'fieldConfig' => [
                'template' => "{label}\n{input}\n{error}",
                'labelOptions' => ['class' => 'col-lg-1 col-form-label mr-lg-3'],
                'inputOptions' => ['class' => 'col-lg-3 form-control'],
                'errorOptions' => ['class' => 'col-lg-7 invalid-feedback'],
            ],
        ]); ?>
    
        <?= $form->field($model, 'surname')->textInput() ?>
    
        <?= $form->field($model, 'name')->textInput() ?>
    
        <?= $form->field($model, 'middle_name')->textInput() ?>
    
        <div class="form-group">
            <div>
                <?= Html::submitButton('Создать', ['class' => 'btn btn-success', 'name' => 'create-button']) ?>
            </div>
        </div>
    
        <?php ActiveForm::end(); ?>
    </div>
    <div>
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
                    'visibleButtons' => [
                        'delete' => function ($model, $key, $index) {
                            return !Yii::$app->user->isGuest && Users::findOne(Yii::$app->user->id)->getTitleRoles() == 'Admin';
                        },
                    ]
                ],
            ],
        ]); ?>
    </div>
</div>