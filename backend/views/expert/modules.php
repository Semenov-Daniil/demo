<?php

/** @var yii\web\View $this */
/** @var yii\bootstrap5\ActiveForm $form */

/** @var app\models\Modules $model */
/** @var yii\data\ActiveDataProvider $dataProvider */

use common\assets\AppAsset;
use common\widgets\Alert;
use yii\bootstrap5\Modal;
use yii\grid\ActionColumn;
use yii\grid\GridView;
use yii\helpers\Html;
use yii\web\View;
use yii\web\YiiAsset;
use yii\widgets\Pjax;

$this->title = 'Модули';

$this->registerJsFile('@web/js/modules.js', ['depends' => AppAsset::class]);

?>

<div class="row">
    <?php Pjax::begin([
        'id' => 'pjax-modules',
        'enablePushState' => false,
        'timeout' => 10000,
        'options' => [
            'data' => [
                'pjax-grid' => true
            ]
        ]
    ]); ?>

        <?= $this->render('_modules-list', [
            'dataProvider' => $dataProvider
        ])?>
    
    <?php Pjax::end(); ?>
</div>
