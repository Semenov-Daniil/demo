<?php

use common\widgets\Alert;
use yii\bootstrap5\Html;
use yii\web\View;
use yii\web\YiiAsset;
use yii\widgets\Pjax;

/** @var yii\web\View $this */
/** @var yii\bootstrap5\ActiveForm $form */
/** @var app\models\ExpertsCompetencies $model */
/** @var app\models\ExpertsCompetencies $dataProvider */
/** @var array $options */

$this->title = 'Эксперты';

$this->registerJsFile('@web/js/experts.js', ['depends' => YiiAsset::class]);
$this->registerJsFile('@web/js/pages/form-input-spin.init.js', ['depends' => YiiAsset::class]);
?>

<div class="col-12 site-experts">

    <?php Pjax::begin([
        'id' => 'pjax-add-expert',
        'enablePushState' => false,
        'timeout' => 10000,
    ]); ?>

        <?= $this->render('_expert-form', [
            'model' => $model
        ]); ?>

    <?php Pjax::end(); ?>

    <?php Pjax::begin([
        'id' => 'pjax-experts',
        'enablePushState' => false,
        'timeout' => 10000,
    ]); ?>

        <?= $this->render('_experts-list', [
            'dataProvider' => $dataProvider
        ]); ?>

    <?php Pjax::end(); ?>
</div>

