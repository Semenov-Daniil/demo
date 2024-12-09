<?php

use app\widgets\Alert;
use yii\bootstrap5\Html;
use yii\web\YiiAsset;
use yii\widgets\Pjax;

/** @var yii\web\View $this */
/** @var yii\bootstrap5\ActiveForm $form */
/** @var app\models\ExpertsCompetencies $model */
/** @var app\models\ExpertsCompetencies $dataProvider */

$this->title = 'Эксперты';

$this->registerJsFile('/js/experts.js', ['depends' => YiiAsset::class]);

?>

<div class="site-experts">

    <h3 class="mb-3"><?= Html::encode($this->title) ?></h3>

    <?php Pjax::begin([
        'id' => 'pjax-experts',
        'enablePushState' => false,
        'timeout' => 10000,
        'linkSelector' => false,
    ]); ?>
        <?= Alert::widget(); ?>

        <?= $this->render('_expert-form', [
            'model' => $model
        ]); ?>

        <?= $this->render('_experts-list', [
            'dataProvider' => $dataProvider
        ]); ?>
    <?php Pjax::end(); ?>
</div>

