<?php

use common\models\EncryptedPasswords;
use yii\bootstrap5\ActiveForm;
use yii\bootstrap5\Html;
use yii\grid\ActionColumn;
use yii\grid\GridView;

/** @var app\models\ExpertsEvents $dataProvider */
?>

<div class="card experts-list">
    <?= Html::beginForm(['experts'], 'delete', [
        'class' => 'delete-experts-form',
        'data' => [
            'pjax' => true
        ],
    ])?>
        <div class="card-header align-items-center d-flex position-relative border-bottom-0">
            <h4 class="card-title mb-0 flex-grow-1">Эксперты</h4>
            <?= Html::a('Удалить выбранные', ['delete-experts'], ['class' => 'btn btn-sm btn-danger btn-delete-check position-absolute bottom-0 end-0 translate-middle-y me-3 d-none', 'data' => ['pjax' => 0]])?>
        </div>

        <div class="card-body">
            <?= GridView::widget([
                'dataProvider' => $dataProvider,
                'pager' => [
                    'class' => \yii\bootstrap5\LinkPager::class,
                    'listOptions' => [
                        'class' => 'pagination m-0',
                    ],
                ],
                'tableOptions' => [
                    'class' => 'table align-middle table-nowrap table-hover table-borderless mb-0 border-bottom',
                ],
                'layout' => "
                    <div class='table-responsive table-card table-responsive'>
                        <div>
                            {items}
                        </div>
                        <div class='d-flex gap-2 flex-wrap justify-content-between p-3'>
                            <div class='gridjs-summary'>
                                {summary}
                            </div>
                            <div>
                                {pager}
                            </div>
                        </div>
                    </div>
                ",
                'columns' => [
                    [
                        'class' => 'yii\grid\CheckboxColumn',

                        'header' => Html::checkBox('selection_all', false, [
                            'class' => 'select-on-check-all form-check-input',
                        ]),
                        'headerOptions' => [
                            'class' => 'text-center'
                        ],

                        'contentOptions' => [
                            'class' => 'text-center'
                        ],
                        'cssClass' => 'form-check-input',

                        'checkboxOptions' => function ($model, $key, $index, $column) {

                            if (Yii::$app->user->id == $model['id']) {
                                return ['disabled' => true, 'class' => 'd-none'];
                            }
                        },

                        'visible' => ($dataProvider->totalCount > 1)
                    ],
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
                                return Html::a('<i class="ri-delete-bin-2-line"></i>', ['delete-experts', 'id' => $model['id']], ['class' => 'btn btn-icon btn-soft-danger ms-auto btn-delete', 'data' => ['pjax' => 0]]);
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
    <?php Html::endForm(); ?>
</div>

