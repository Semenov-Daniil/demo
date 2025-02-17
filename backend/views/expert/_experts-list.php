<?php

use yii\bootstrap5\Html;
use yii\grid\ActionColumn;
use yii\grid\GridView;

/** @var app\models\ExpertsEvents $dataProvider */
?>

<div class="experts-list">
    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'pager' => [
            'class' => \yii\bootstrap5\LinkPager::class
        ],
        'layout' => "
            <div>{pager}</div>
            <div class='mt-3'>{items}</div>
            <div class='mt-3'>{pager}</div>",
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
                'label' => 'Событие',
                'value' => function($model) {
                    return $model['event'];
                },
            ],
            [
                'label' => 'Кол-во модулей',
                'value' => function($model) {
                    return $model['countModules'];
                },
            ],
            [
                'class' => ActionColumn::class,
                'template' => '{delete}',
                'buttons' => [
                    'delete' => function ($url, $model, $key) {
                        return Html::a('Удалить', ['delete-experts', 'id' => $model['id']], ['class' => 'btn btn-danger btn-delete', 'data' => ['pjax' => 0]]);
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
</div>

