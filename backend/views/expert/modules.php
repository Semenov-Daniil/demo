<?php

/** @var yii\web\View $this */
/** @var yii\bootstrap5\ActiveForm $form */

/** @var app\models\Modules $dataProvider */

use common\widgets\Alert;
use yii\grid\ActionColumn;
use yii\grid\GridView;
use yii\helpers\Html;
use yii\web\View;
use yii\widgets\Pjax;

$this->title = 'Модули';

$this->registerJsFile('@web/js/modules.js', ['depends' => 'yii\web\JqueryAsset']);
?>
<div class="col-12 site-modules">
    <?php Pjax::begin([
        'id' => 'pjax-modules',
        'enablePushState' => false,
        'timeout' => 10000,
    ]); ?>
        <?= Alert::widget(); ?>
        <?= GridView::widget([
            'dataProvider' => $dataProvider,
            'layout' => "
                <div class=\"mt-3\">{pager}</div>\n
                <div >{items}</div>\n
                <div class=\"mt-3\">{pager}</div>",
            'pager' => ['class' => \yii\bootstrap5\LinkPager::class],
            'columns' => [
                [
                    'label' => '№ Модуля',
                    'value' => function($model, $key, $index, $column) {
                        return 'Модуль ' . $model->number;
                    }
                ],
                [
                    'label' => 'Статус',
                    'format' => 'raw',
                    'value' => function ($model) {
                        return "<div class=\"form-check form-switch\">" 
                                . Html::checkbox('status_' . $model->number, $model->status, ['data' => ['id' => $model->id, 'pjax' => true], 'class' => 'form-check-input switch-status'])
                                . "</div>";
                    },
                ],
                [
                    'class' => ActionColumn::class,
                    'template' => '{delete}',
                    'buttons' => [
                        'delete' => function ($url, $model, $key) {
                            return Html::a('Удалить', ['delete-modules', 'id' => $model->id], ['data' => ['pjax' => 0], 'class' => 'btn btn-danger btn-delete']);
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
