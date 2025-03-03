<?php

use common\models\EncryptedPasswords;
use yii\bootstrap5\Html;
use yii\grid\ActionColumn;
use yii\grid\GridView;

/** @var yii\data\ActiveDataProvider $dataProvider */
?>

<?php if ($dataProvider->totalCount): ?> 
    <div class="p-3 d-flex flex-wrap gap-3 justify-content-end">
        <?= Html::a('<span class="d-flex align-items-center"><i class="ri-export-fill align-middle fs-16 me-2"></i> Экспорт</span>', ['/export-students'], ['class' => 'btn btn-secondary btn-export', 'data' => ['pjax' => 0]]) ?>
        <?= Html::button('<span class="d-flex align-items-center"><i class="ri-check-double-line align-middle fs-16 me-2"></i> Выбрать все</span>', ['class' => 'btn btn-primary btn-select-all-students']) ?>
        <?= Html::button('<span class="d-flex align-items-center"><i class="ri-delete-bin-2-line align-middle fs-16 me-2"></i> Удалить</span>', ['class' => 'btn btn-danger btn-delete-selected-students', 'disabled' => true]) ?>
    </div>
<?php endif; ?>

<div class="card students-list">
    <div class="card-header align-items-center d-flex position-relative border-bottom-0">
        <h4 class="card-title mb-0 flex-grow-1">Студенты</h4>
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
                'nextPageLabel' => '<i class="ri-arrow-right-double-line"></i>',
            ],
            'emptyText' => 'Ничего не найдено. Добавьте студентов.',
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
                    'name' => 'students',

                    'header' => Html::checkBox('students_all', false, [
                        'class' => 'select-on-check-all form-check-input students-check',
                    ]),
                    'headerOptions' => [
                        'class' => 'cell-selected cell-checkbox text-center form-check d-table-cell cursor-pointer'
                    ],

                    'contentOptions' => [
                        'class' => 'cell-selected cell-checkbox text-center form-check d-table-cell cursor-pointer'
                    ],

                    'cssClass' => 'form-check-input students-check',

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
                    'options' => [
                        'class' => 'col-4'
                    ],
                    'visible' => $dataProvider->totalCount
                ],
                [
                    'label' => 'Логин/Пароль',
                    'value' => function ($model) {
                        return $model['login'] . '/' . EncryptedPasswords::decryptByPassword($model['encryptedPassword']);
                    },
                    'options' => [
                        'class' => 'col-4'
                    ],
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
                            return Html::button('<i class="ri-delete-bin-2-line"></i>', ['class' => 'btn btn-icon btn-soft-danger ms-auto btn-delete', 'data' => ['id' => $model['students_id']]]);
                        }
                    ],
                    'visible' => $dataProvider->totalCount
                ],
            ],
        ]); ?>
    </div>
</div>

