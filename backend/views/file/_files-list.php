<?php

use yii\bootstrap5\Html;
use yii\grid\ActionColumn;
use yii\grid\GridView;

/** @var yii\data\ActiveDataProvider $dataProvider */
/** @var common\models\Events|null $event */

?>

<?php if ($dataProvider->totalCount): ?> 
<div class="p-3 d-flex flex-wrap gap-3 justify-content-end">
    <?= Html::button('<span><i class="ri-check-double-line fs-16 me-2"></i> Выбрать все</span>', ['class' => 'btn btn-primary btn-select-all-files']) ?>
    <?= Html::button('<i class="ri-delete-bin-2-line fs-16 me-2"></i> Удалить', ['class' => 'btn btn-danger btn-delete-selected-files', 'disabled' => true]) ?>
    <?// Html::button('<i class="ri-download-2-line fs-16 me-2"></i> Скачать', ['class' => 'btn btn-secondary btn-download-selected-files', 'disabled' => true]) ?>
</div>
<?php endif; ?>

<div class="card">
    <div class="card-header align-items-center d-flex position-relative <?= ($dataProvider->totalCount ? 'border-bottom-0' : '') ?>">
        <h4 class="card-title mb-0 flex-grow-1">Файлы<?= (!is_null($event) ? '. ' . $event?->expert->fullName . '. ' . $event?->title : ''); ?></h4>
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
            'emptyText' => (is_null($event) ? 'Выберите чемпионат.' : 'Ничего не найдено. Добавьте файлы.'),
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
                    'name' => 'files',

                    'header' => Html::checkBox('files_all', false, [
                        'class' => 'select-on-check-all form-check-input files-check',
                    ]),
                    'headerOptions' => [
                        'class' => 'cell-selected cell-checkbox text-center form-check d-table-cell cursor-pointer'
                    ],

                    'contentOptions' => [
                        'class' => 'cell-selected cell-checkbox text-center form-check d-table-cell cursor-pointer'
                    ],

                    'cssClass' => 'form-check-input files-check',

                    'options' => [
                        'class' => 'col-1'
                    ],

                    'visible' => $dataProvider->totalCount,
                ],
                [
                    'label' => 'Файл',
                    'format' => 'raw',
                    'content' => function ($model) {
                        $file = Yii::getAlias('@events/' . $model['dir_title'] . '/' . $model['save_name'] . '.' . $model['extension']);
                        $fileSize = file_exists($file) ? filesize($file) : null;
                        return '<h5 class="fs-14 mb-1">'. $model['origin_name'] . '.' . $model['extension'] .'</h5>' 
                                . (is_null($fileSize) ? '' : '<p class="fs-13 text-muted mb-0">' . Yii::$app->fileComponent->formatSizeUnits($fileSize)) . '</p>';
                    },
                    'options' => [
                        'class' => 'col-4'
                    ],
                    'visible' => $dataProvider->totalCount,
                ],
                [
                    'label' => 'Расположение',
                    'value' => function ($model) {
                        return $model['directory'];
                    },
                    'visible' => $dataProvider->totalCount,
                ],
                [
                    'class' => ActionColumn::class,
                    'template' => '<div class="d-flex flex-wrap gap-2 justify-content-end">
                        {delete}
                        {download}
                    </div>',
                    'buttons' => [
                        'delete' => function ($url, $model, $key) {
                            return Html::button('<i class="ri-delete-bin-2-line"></i>', ['class' => 'btn btn-icon btn-soft-danger btn-delete', 'data' => ['id' => $model['id']]]);
                        },
                        'download' => function ($url, $model, $key) use ($event) {
                            return Html::a('<i class="ri-download-2-line"></i>', ['/download', 'event' => $event->id, 'filename' => $model['save_name']], ['class' => 'btn btn-icon btn-soft-secondary', 'data' => ['id' => $model['save_name'], 'pjax' => 0]]);
                        },
                        'download-btn' => function ($url, $model, $key) {
                            return Html::button('<i class="ri-download-2-line"></i>', ['class' => 'btn btn-icon btn-soft-secondary btn-download', 'data' => ['filename' => $model['save_name']]]);
                        },
                    ],
                    'visible' => $dataProvider->totalCount,
                ],
            ],
        ]); ?>
    </div>
</div>

