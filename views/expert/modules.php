<?php

/** @var yii\web\View $this */
/** @var yii\bootstrap5\ActiveForm $form */

/** @var app\models\Modules $dataProvider */

use yii\grid\ActionColumn;
use yii\grid\GridView;
use yii\helpers\Html;
use yii\web\View;
use yii\widgets\Pjax;

$this->title = 'Модули';

$changeStatus = <<<JS
    $("#pjax-modules").on("change", ".switch-status", function(event) {
        event.preventDefault();
        $.ajax({
            type: "PATH",
            url: "/modules",
            dataType: 'json',
            contentType: 'application/json; charset=utf-8',
            data: JSON.stringify({
                'id': event.target.dataset.id,
                'status': event.target.checked ? 1 : 0,
                '_csrf': $('meta[name="csrf-token"]').attr('content')
            }),
            success: function (response) {
                $.pjax.reload({container: "#pjax-modules"});
            },
            error: function(xhr, status, error) {
                event.target.checked = !event.target.checked;
            }
        });
    });
JS;

$deleteModule = <<<JS
    $("#pjax-modules").on("mousedown", ".btn-delete", function(event_mousedown) {
        event_mousedown.preventDefault();
        $(this).on("mouseup", function(event_mouseup) {
            event_mouseup.preventDefault();
            $.ajax({
                type: "DELETE",
                url: "/modules/" + event_mouseup.target.dataset.id,
                success: function (response) {
                    $.pjax.reload({container: "#pjax-modules"});
                },
                error: function(xhr, status, error) {
                    
                }
            });
        });
    });
JS;

$this->registerJs($changeStatus);
$this->registerJs($deleteModule);

?>
<div class="site-modules">
    <h1><?= Html::encode($this->title) ?></h1>
    
    <div>
        <?php Pjax::begin([
            'id' => 'pjax-modules'
        ]); ?>
            <?= GridView::widget([
                'dataProvider' => $dataProvider,
                'pager' => ['class' => \yii\bootstrap5\LinkPager::class],
                'columns' => [
                    [
                        'value' => function($model, $key, $index, $column) {
                            return 'Модуль ' . $model->number;
                        }
                    ],
                    [
                        'class' => 'yii\grid\ActionColumn',
                        'template' => '<div class="form-check form-switch">{status}</div>',
                        'buttons' => [
                            'status' => function ($url, $model, $key) {
                                return Html::checkbox('status_' . $model->number, $model->status, ['data' => ['id' => $model->id, 'pjax' => true], 'class' => 'form-check-input switch-status']);
                            },
                        ],
                    ],
                    [
                        'class' => ActionColumn::class,
                        'template' => '{delete}',
                        'buttons' => [
                            'delete' => function ($url, $model, $key) {
                                return Html::button('Удалить', ['data' => ['id' => $model->id, 'pjax' => true], 'class' => 'btn btn-danger btn-delete']);
                            }
                        ],
                        'visibleButtons' => [
                            'delete' => function ($model, $key, $index) {
                                return Yii::$app->user->can('expert');
                            }
                        ]
                    ],
                ],
            ]); ?>
        <?php Pjax::end(); ?>
    </div>
</div>
