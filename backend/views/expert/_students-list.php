<?php

use common\models\EncryptedPasswords;
use yii\bootstrap5\Html;
use yii\grid\ActionColumn;
use yii\grid\GridView;

/** @var app\models\StudentsEvents $dataProvider */
?>

<div class="students-list">
    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'pager' => [
            'class' => \yii\bootstrap5\LinkPager::class
        ],
        'layout' => "
            <div>{pager}</div>
            <div class='mt-3'>{items}</div>
            <div class='mt-3'>{pager}</div>
        ",
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
                    return $model['login'] . '/' . EncryptedPasswords::decryptByPassword($model['encryptedPassword']);
                },
            ],
            [
                'class' => ActionColumn::class,
                'template' => '{delete}',
                'buttons' => [
                    'delete' => function ($url, $model, $key) {
                        return Html::a('Удалить', ['delete-students', 'id' => $model['students_id']], ['class' => 'btn btn-danger btn-delete', 'data' => ['pjax' => 0]]);
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
</div>

