<?php

use yii\bootstrap5\Html;
use yii\grid\ActionColumn;
use yii\grid\GridView;

/** @var app\models\FilesEvents $dataProvider */
?>

<div class="files-list">
    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'pager' => [
            'class' => \yii\bootstrap5\LinkPager::class
        ],
        'layout' => "
            <div>{pager}</div>
            <div class='mt-3'>{items}</div>
            <div class='mt-3'>{pager}</div>
        ",
        'columns' => [
            [
                'label' => 'Файл',
                'format' => 'raw',
                'value' => function ($model) {
                    return Html::a($model['originName'], ["/download/" . $model['dirTitle'] . "/" . $model['saveName']], ['data' => ['pjax' => '0']]);
                },
            ],
            [
                'class' => ActionColumn::class,
                'template' => '<div class="d-flex flex-wrap gap-2 justify-content-end">{delete}{download}</div>',
                'buttons' => [
                    'delete' => function ($url, $model, $key) {
                        return Html::a('Удалить', ['delete-files', 'id' => $model['fileId']], ['class' => 'btn btn-danger btn-delete', 'data' => ['pjax' => 0]]);
                    },
                    'download' => function ($url, $model, $key) {
                        return Html::a('Скачать', ["/download/" . $model['dirTitle'] . "/" . $model['saveName']], ['class' => 'btn btn-primary', 'data' => ['pjax' => 0]]);
                    }
                ],
            ],
        ],
    ]); ?>
</div>

