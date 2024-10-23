<?php

/** @var yii\web\View $this */
/** @var yii\bootstrap5\ActiveForm $form */

/** @var app\models\Modules $dataProvider */

use yii\grid\ActionColumn;
use yii\grid\GridView;
use yii\helpers\Html;
use yii\widgets\Pjax;

$this->title = 'Модули';
?>
<div class="site-modules">
    <h1><?= Html::encode($this->title) ?></h1>
    
    <div>
        <?php Pjax::begin([
            'id' => 'ajax'
        ]); ?>
            <?= GridView::widget([
                'dataProvider' => $dataProvider,
                'pager' => ['class' => \yii\bootstrap5\LinkPager::class],
                'columns' => [
                    [
                        'value' => function($model, $key, $index, $column) {
                            return 'Модуль ' . ($index+1);
                        }
                    ],
                    [
                        'class' => 'yii\grid\ActionColumn',
                        'template' => '{status}',
                        'buttons' => [
                            'status' => function ($url, $model, $key) {
                                return Html::beginForm(['/modules', 'id' => $model->id], 'post', ['data-pjax' => '', 'class' => 'form-check form-switch toggle-status-form'])
                                    . Html::checkbox('status_' . $key, $model->status, ['class' => 'status-switch form-check-input'])
                                    . Html::endForm();
                            },
                        ],
                    ],
                    [
                        'class' => ActionColumn::class,
                        'template' => '{delete}',
                        'buttons' => [
                            'delete' => function ($url, $model, $key) {
                                return
                                    Html::beginForm(['/delete-module'], 'post', ['data' => ['pjax' => true]])
                                    . Html::submitButton('Удалить', ['class' => 'btn btn-danger', 'data' => ['method' => 'POST', 'params' => ['id' => $model->id]]])
                                    . Html::endForm()
                                ;
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
