<?php

use yii\bootstrap5\Html;
use yii\bootstrap5\LinkPager;
use yii\grid\ActionColumn;
use yii\grid\GridView;
use yii\widgets\ListView;

/** @var yii\data\ActiveDataProvider $dataProvider */
/** @var common\models\Events|null $event */

?>

<div class="">
    <h4 class="card-title m-0">Данные студентов<?= (!is_null($event) ? ". {$event?->expert->fullName}. {$event?->title}" : ''); ?></h4>
</div>

<?= ListView::widget([
        'dataProvider' => $dataProvider,
        'itemOptions' => ['class' => 'mt-3 item'],
        'itemView' => '_student-item',
        'layout' => "
            <div class=\"row row-cols-1 row-cols-md-2 row-cols-xxl-3\">
                {items}
            </div>
            ". ($dataProvider->totalCount 
            ? 
                "
                <div class=\"d-flex gap-2 flex-wrap justify-content-between align-items-center p-3 ps-0 gridjs-pagination\">
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
        ",
        'pager' => [
            'class' => \yii\bootstrap5\LinkPager::class,
            'listOptions' => [
                'class' => 'pagination pagination-separated m-0',
            ],
            'maxButtonCount' => 5,
            'prevPageLabel' => '<i class="ri-arrow-left-double-line"></i>',
            'nextPageLabel' => '<i class="ri-arrow-right-double-line"></i>',
        ],
        'emptyText' => (is_null($event) ? 'Выберите событие.' : 'Ничего не найдено. ' . Html::a('Добавить студентов <i class="ri-arrow-right-s-line align-middle lh-1"></i>', ['/student', 'event' => $event?->id], ['class' => 'card-link link-secondary', 'data-pjax' => 0])),
        'emptyTextOptions' => [
            'class' => 'text-center pt-3',
        ],
]); ?>
