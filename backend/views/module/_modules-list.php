<?php

use backend\assets\AppAsset as BackendAppAsset;
use common\models\EncryptedPasswords;
use yii\bootstrap5\ActiveForm;
use yii\bootstrap5\Html;
use yii\grid\ActionColumn;
use yii\grid\GridView;

/** @var app\models\Modules $dataProvider */
/** @var common\models\Events|null $event */

$this->registerJsFile('@web/js/modules/module/modulesList.js', ['depends' => [BackendAppAsset::class]], 'modulesList');

?>

<?php if (!is_null($event)): ?> 
<div class="p-3 pt-0 d-flex flex-wrap gap-3 justify-content-end">
    <?= Html::submitButton('
        <span class="d-flex align-items-center cnt-text">
        <i class="ri-add-line align-middle fs-16 me-2"></i> Добавить
        </span>
        <span class="d-flex align-items-center d-none cnt-load">
        <span class="spinner-border flex-shrink-0" role="status">
        </span>
        <span class="flex-grow-1 ms-2">
        Добавление...
        </span>
        </span>
        ', ['class' => 'btn btn-success btn-load btn-create-module', 'form' => 'form-create-module']) ?>
    <?php if ($dataProvider->totalCount): ?> 
        <?= Html::button('<span><i class="ri-check-double-line align-middle fs-16 me-2"></i> Выбрать все</span>', ['class' => 'btn btn-primary btn-select-all-modules']) ?>
        <?= Html::button('<i class="ri-brush-2-line align-middle fs-16 me-2"></i> Очистить', ['class' => 'btn btn-secondary btn-clear-selected-modules', 'disabled' => true]) ?>
        <?= Html::button('<i class="ri-delete-bin-2-line align-middle fs-16 me-2"></i> Удалить', ['class' => 'btn btn-danger btn-delete-selected-modules', 'disabled' => true]) ?>
    <?php endif; ?>
</div>
<?php endif; ?>

<div class="card">
    <div class="card-header align-items-center d-flex position-relative border-bottom-0">
        <h4 class="card-title mb-0 flex-grow-1">Модули<?= (!is_null($event) ? '. ' . $event?->expert->fullName . '. ' . $event?->title : ''); ?></h4>
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
            'tableOptions' => [
                'class' => 'table align-middle table-nowrap table-hover table-borderless mb-0 border-bottom',
            ],
            'emptyText' => (is_null($event) ? 'Выберите событие.' : 'Ничего не найдено. Добавьте модуль.'),
            'emptyTextOptions' => [
                'class' => 'text-center',
            ],
            'layout' => "
                <div class=\"table-responsive table-card table-responsive\">
                    <div>
                        {items}
                    </div>
                    ". ($dataProvider->totalCount ? "
                    <div class=\"d-flex gap-2 flex-wrap justify-content-between align-items-center p-3 gridjs-pagination\">
                        <div class=\"text-body-secondary\">
                            {summary}
                        </div>
                        <div>
                            {pager}
                        </div>
                    </div>
                    " : '')."
                </div>
            ",
            'columns' => [
                [
                    'class' => 'yii\grid\CheckboxColumn',
                    'name' => 'modules',

                    'header' => Html::checkBox('modules_all', false, [
                        'class' => 'select-on-check-all form-check-input modules-check',
                    ]),
                    'headerOptions' => [
                        'class' => 'cell-selected cell-checkbox text-center form-check d-table-cell cursor-pointer'
                    ],

                    'contentOptions' => [
                        'class' => 'cell-selected cell-checkbox text-center form-check d-table-cell cursor-pointer'
                    ],

                    'cssClass' => 'form-check-input modules-check',

                    'options' => [
                        'class' => 'col-1'
                    ],

                    'visible' => $dataProvider->totalCount
                ],
                [
                    'label' => 'Модуль',
                    'value' => function($model, $key, $index, $column) {
                        return 'Модуль ' . $model->number;
                    },
                    'options' => [
                        'class' => 'col-4'
                    ],
                    'visible' => $dataProvider->totalCount,
                ],
                [
                    'class' => 'yii\grid\CheckboxColumn',
                    'name' => 'status',

                    'header' => 'Статус',

                    'content' => function ($model, $key, $index, $column) {
                        return Html::checkbox("status", $model->status, [
                            'class' => 'form-check-input switch-status me-2',
                            'data' => ['id' => $model->id],
                            'label' => "<span class=\"fs-6 user-select-none badge ".($model['status'] ? 'bg-success' : 'bg-dark-subtle text-body')."\">".($model['status'] ? 'Онлайн' : 'Офлайн')."</span>",
                            'labelOptions' => [
                                'class' => 'label-badge m-0'
                            ],
                        ]);
                    },

                    'contentOptions' => [
                        'class' => 'cell-checkbox form-check form-switch form-switch-success d-table-cell cursor-pointer ps-2'
                    ],

                    'options' => [
                        'class' => 'col-1'
                    ],

                    'visible' => $dataProvider->totalCount
                ],
                [
                    'class' => ActionColumn::class,
                    'template' => '
                        <div class="d-flex flex-wrap gap-2 justify-content-end">
                            {clear}
                            {delete}
                        </div>
                    ',
                    'buttons' => [
                        'delete' => function ($url, $model, $key) {
                            return Html::button('<i class="ri-delete-bin-2-line ri-lg"></i>', ['class' => 'btn btn-icon btn-soft-danger btn-delete', 'data' => ['id' => $model['id']], 'title' => 'Удалить']);
                        },
                        'clear' => function ($url, $model, $key) {
                            return Html::button('<i class="ri-brush-2-line ri-lg"></i>', ['class' => 'btn btn-icon btn-soft-secondary btn-clear', 'data' => ['id' => $model['id']], 'title' => 'Очистить']);
                        },
                    ],
                    'visible' => $dataProvider->totalCount
                ],
            ],
        ]); ?>
    </div>
</div>

