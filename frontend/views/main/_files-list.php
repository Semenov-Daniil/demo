<?php

use common\assets\AppAsset;
use yii\grid\ActionColumn;
use yii\grid\GridView;
use yii\helpers\Html;
use yii\widgets\Pjax;

/** @var yii\web\View $this */
/** @var yii\data\ActiveDataProvider $files */

?>

<div class="card">
    <div class="card-header align-items-center d-flex position-relative <?= ($files->totalCount ? 'border-bottom-0' : '') ?>">
        <h4 class="card-title mb-0 flex-grow-1">Файлы</h4>
    </div>

    <div class="card-body">
        <?php Pjax::begin([
            'id' => 'pjax-files',
            'enablePushState' => false,
            'timeout' => 10000,
        ])?>
            <?= GridView::widget([
                'dataProvider' => $files,
                'pager' => [
                    'class' => \yii\bootstrap5\LinkPager::class,
                    'listOptions' => [
                        'class' => 'pagination pagination-separated m-0',
                    ],
                    'maxButtonCount' => 5,
                    'prevPageLabel' => '<i class="ri-arrow-left-double-line"></i>',
                    'nextPageLabel' => '<i class="ri-arrow-right-double-line"></i>'
                ],
                'showHeader' => false,
                'emptyText' => 'Ничего не найдено.',
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
                        ". ($files->totalCount 
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
                        'label' => 'Файл',
                        'format' => 'raw',
                        'content' => function ($model) {
                            $file = $model->path;
                            $fileSize = file_exists($file) ? filesize($file) : null;
                            return '<span class="fs-14 mb-1 h5">'. "{$model->name}.{$model->extension}" .'</span>' 
                                    . (is_null($fileSize) ? '' : '<p class="fs-13 text-muted mb-0">' . Yii::$app->fileComponent->formatSizeUnits($fileSize)) . '</p>';
                        },
                        'options' => [
                            'class' => 'col-7'
                        ],
                        'contentOptions' => [
                            'class' => 'text-wrap text-break'
                        ],
                        'visible' => $files->totalCount,
                    ],
                    [
                        'label' => 'Расположение',
                        'value' => function ($model) {
                            return $model->moduleTitle;
                        },
                        'options' => [
                            'class' => 'col-auto'
                        ],
                        'contentOptions' => [
                            'class' => 'text-center'
                        ],
                        'visible' => $files->totalCount,
                    ],
                    [
                        'class' => ActionColumn::class,
                        'template' => '<div class="d-flex flex-wrap gap-2 justify-content-end">{download}</div>',
                        'buttons' => [
                            'download' => function ($url, $model, $key) {
                                return Html::a('<i class="ri-download-2-line"></i>', ["download/{$model->id}"], ['class' => 'btn btn-icon btn-soft-secondary', 'data' => ['pjax' => 0], 'target' => '_blank', 'download' => true]);
                            },
                        ],
                        'visible' => $files->totalCount,
                    ],
                ],
            ]); ?>
        <?php Pjax::end(); ?>
    </div>
</div>