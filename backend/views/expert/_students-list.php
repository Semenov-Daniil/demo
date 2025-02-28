<?php

use common\models\EncryptedPasswords;
use yii\bootstrap5\Html;
use yii\grid\ActionColumn;
use yii\grid\GridView;

/** @var app\models\StudentsEvents $dataProvider */
?>

<div class="card students-list">
    <div class="card-header align-items-center d-flex position-relative border-bottom-0">
        <h4 class="card-title mb-0 flex-grow-1">Студенты</h4>
    </div>

    <div class="card-body">
        <?= Html::beginForm(['delete-students'], 'delete', [
            'class' => 'delete-students-form',
            'data' => [
                'pjax' => true
            ],
        ])?>
            <?= GridView::widget([
                'dataProvider' => $dataProvider,
                'pager' => [
                    'class' => \yii\bootstrap5\LinkPager::class,
                    'listOptions' => [
                        'class' => 'pagination pagination-separated m-0',
                    ],
                    'maxButtonCount' => 5,
                    'prevPageLabel' => '<i class="ri-arrow-left-double-line"></i>',
                    'nextPageLabel' => '<i class="ri-arrow-right-double-line"></i>',
                ],
                'tableOptions' => [
                    'class' => 'table align-middle table-nowrap table-hover table-borderless mb-0 border-bottom',
                ],
                'emptyText' => false,
                'layout' => "
                    <div class=\"table-responsive table-card table-responsive\">
                        <div>
                            {items}
                        </div>
                        <div id=\"collapseAllActions\" class=\"collapse\">
                            <div class=\"p-3 row gx-0 gy-0 gap-2\">
                                <div class=\"col text-body-secondary\">
                                    Действие с выбранными студентами:
                                </div>
                                <div class=\"col-auto\">
                                    ".Html::a('Удалить выбранные', ['delete-students'], ['class' => 'btn btn-danger btn-delete-check', 'data' => ['method' => 'delete']])."
                                </div>
                            </div>
                        </div>
                        <div class=\"d-flex gap-2 flex-wrap justify-content-between align-items-center p-3 gridjs-pagination\">
                            <div class=\"text-body-secondary\">
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
                            'class' => 'text-center form-check'
                        ],

                        'contentOptions' => [
                            'class' => 'text-center'
                        ],
                        'cssClass' => 'form-check-input',

                        'options' => [
                            'class' => 'col-1'
                        ],

                        'visible' => $dataProvider->totalCount
                    ],
                    [
                        'label' => 'Полное имя',
                        'value' => function ($model) {
                            return $model['fullName'];
                        },
                        'visible' => $dataProvider->totalCount
                    ],
                    [
                        'label' => 'Логин/Пароль',
                        'value' => function ($model) {
                            return $model['login'] . '/' . EncryptedPasswords::decryptByPassword($model['encryptedPassword']);
                        },
                        'visible' => $dataProvider->totalCount
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
                                return Html::a('<i class="ri-delete-bin-2-line"></i>', ['delete-students', 'id' => $model['students_id']], ['class' => 'btn btn-icon btn-soft-danger ms-auto btn-delete', 'data' => ['method' => 'delete']]);
                            }
                        ],
                        'visibleButtons' => [
                            'delete' => function ($model, $key, $index) {
                                return Yii::$app->user->can('expert');
                            }
                        ],
                        'visible' => $dataProvider->totalCount
                    ],
                ],
            ]); ?>
        <?php Html::endForm(); ?>
    </div>
</div>

