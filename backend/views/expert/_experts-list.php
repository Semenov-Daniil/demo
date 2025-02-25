<?php

use common\models\EncryptedPasswords;
use yii\bootstrap5\Html;
use yii\grid\ActionColumn;
use yii\grid\GridView;

/** @var app\models\ExpertsEvents $dataProvider */
?>

<div class="card experts-list">
    <div class="card-header align-items-center d-flex">
        <h4 class="card-title mb-0 flex-grow-1">Эксперты</h4>
    </div>

    <div class="card-body">
        <?= GridView::widget([
            'dataProvider' => $dataProvider,
            'pager' => [
                'class' => \yii\bootstrap5\LinkPager::class
            ],
            'tableOptions' => [
                'class' => 'table align-middle table-nowrap table-hover table-borderless mb-0',
            ],
            'layout' => "
                <div>{pager}</div>
                <div class='table-responsive table-card table-responsive'>{items}</div>
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
                    'options' => [
                        'class' => 'col-1'
                    ],
                    'contentOptions' => [
                        'class' => 'text-center'
                    ],
                ],
                [
                    'class' => ActionColumn::class,
                    'template' => '
                        <div class="d-flex flex-wrap gap-2">
                            {delete}
                        </div>
                    ',
                    'buttons' => [
                        'delete' => function ($url, $model, $key) {
                            return Html::a('<i class="ri-delete-bin-2-line"></i>', ['delete-experts', 'id' => $model['id']], ['class' => 'btn btn-soft-danger ms-auto btn-delete', 'data' => ['pjax' => 0]]);
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
</div>

