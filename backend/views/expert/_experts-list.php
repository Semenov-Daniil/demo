<?php

use common\models\EncryptedPasswords;
use yii\bootstrap5\ActiveForm;
use yii\bootstrap5\Html;
use yii\grid\ActionColumn;
use yii\grid\GridView;

/** @var yii\data\ActiveDataProvider $dataProvider */
?>

<?php if ($dataProvider->totalCount): ?> 
<div class="p-3 d-flex flex-wrap gap-3 justify-content-end">
    <?= Html::button('
        <span>
            <i class="ri-check-double-line align-middle fs-16 me-2"></i> Выбрать все
        </span>
    ', ['class' => 'btn btn-primary btn-select-all-experts']) ?>
    <?= Html::button('<i class="ri-delete-bin-2-line align-middle fs-16 me-2"></i> Удалить', ['class' => 'btn btn-danger btn-delete-selected-experts', 'disabled' => true]) ?>
</div>
<?php endif; ?>

<div class="card">
    <div class="card-header align-items-center d-flex position-relative border-bottom-0">
        <h4 class="card-title mb-0 flex-grow-1">Эксперты</h4>
    </div>

    <div class="card-body">
        <?= GridView::widget([
            'dataProvider' => $dataProvider,
            'pager' => [
                'class' => \yii\bootstrap5\LinkPager::class,
                'listOptions' => [
                    'class' => 'pagination pagination-separated m-0',
                ],
                'maxButtonCount' => 5,
                'prevPageLabel' => '<i class="ri-arrow-left-double-line"></i>',
                'nextPageLabel' => '<i class="ri-arrow-right-double-line"></i>'
            ],
            'emptyText' => 'Ничего не найдено. Добавьте эксперта.',
            'emptyTextOptions' => [
                'class' => 'text-center',
            ],
            'tableOptions' => [
                'class' => 'table align-middle table-nowrap table-hover table-borderless mb-0 border-bottom',
            ],
            'layout' => "
                <div class=\"table-responsive table-card table-responsive\">
                    <div>
                        {items}
                    </div>
                    ". ($dataProvider->totalCount 
                    ? 
                        "
                        <div class=\"d-flex gap-2 flex-wrap justify-content-between align-items-center p-3 gridjs-pagination\">
                            <div class=\"text-body-secondary\">
                                {summary}
                            </div>
                            <div>
                                {pager}
                            </div>
                        </div>
                        "
                    : 
                        ''
                    )."
                </div>
            ",
            'columns' => [
                [
                    'class' => 'yii\grid\CheckboxColumn',
                    'name' => 'experts',

                    'header' => Html::checkBox('experts_all', false, [
                        'class' => 'select-on-check-all form-check-input experts-check',
                    ]),
                    'headerOptions' => [
                        'class' => 'cell-selected cell-checkbox text-center form-check d-table-cell cursor-pointer'
                    ],

                    'contentOptions' => [
                        'class' => 'cell-selected cell-checkbox text-center form-check d-table-cell cursor-pointer'
                    ],

                    'checkboxOptions' => function ($model, $key, $index, $column) {
                        if (Yii::$app->user->id == $model['id']) {
                            return ['disabled' => true, 'class' => 'd-none'];
                        }
                    },

                    'cssClass' => 'form-check-input modules-check',

                    'options' => [
                        'class' => 'col-1'
                    ],

                    'visible' => ($dataProvider->totalCount > 1),
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
                            return Html::button('<i class="ri-delete-bin-2-line"></i>', ['class' => 'btn btn-icon btn-soft-danger ms-auto btn-delete', 'data' => ['id' => $model['id']]]);
                        }
                    ],
                    'visibleButtons' => [
                        'delete' => function ($model, $key, $index) {
                            return Yii::$app->user->id !== $model['id'];
                        }
                    ],
                    'visible' => $dataProvider->totalCount
                ],
            ],
        ]); ?>
    </div>
</div>

