<?php

use backend\assets\AppAsset as BackendAppAsset;
use common\assets\AppAsset;
use common\assets\CleaveAsset;
use common\models\EncryptedPasswords;
use common\widgets\Alert;
use yii\bootstrap5\Html;
use yii\bootstrap5\Modal;
use yii\web\View;
use yii\web\YiiAsset;
use yii\widgets\Pjax;

/** @var yii\web\View $this */
/** @var yii\bootstrap5\ActiveForm $form */
/** @var app\models\ExpertsCompetencies $model */
/** @var yii\data\ActiveDataProvider $dataProvider */
/** @var array $options */

BackendAppAsset::register($this);

$this->title = 'Эксперты';

$this->registerJsFile('@web/js/modules/expert/experts.js', ['depends' => BackendAppAsset::class]);

?>

<div class="row">
    <div>
        <?= $this->render('_expert-create', [
            'model' => $model
        ]); ?>
    </div>
</div>

<div class="row">
    <?php Pjax::begin([
        'id' => 'pjax-experts',
        'timeout' => 5000,
        'clientOptions' => ['cache' => true],
    ]); ?>

        <?= $this->render('_experts-list', [
            'dataProvider' => $dataProvider
        ]); ?>

    <?php Pjax::end(); ?>
</div>

<?php Modal::begin([
    'id' => 'modal-update-expert',
    'size' => Modal::SIZE_DEFAULT,
    'title' => 'Редактирование эксперта',
    'centerVertical' => true,
    'headerOptions' => [
        'class' => 'bg-light p-3',
    ]
]); ?>

<div class="row">
    <div class="d-flex flex-column justify-content-end mb-3 placeholder-glow">
        <div class="mr-lg-3 placeholder col-4 mb-2 rounded-1"></div>
        <div class="form-control placeholder p-3"></div>
    </div>
    <div class="d-flex flex-column justify-content-end mb-3 placeholder-glow">
        <div class="mr-lg-3 placeholder col-2 mb-2 rounded-1"></div>
        <div class="form-control placeholder p-3"></div>
    </div>
    <div class="d-flex flex-column justify-content-end mb-3 placeholder-glow">
        <div class="mr-lg-3 placeholder col-2 mb-2 rounded-1"></div>
        <div class="form-control placeholder p-3"></div>
    </div>
    <div class="col-12 d-flex flex-wrap gap-2 justify-content-end">
        <button type="button" class="btn btn-info disabled placeholder col-2"></button>
        <button type="submit" class="btn btn-success disabled placeholder col-3"></button>
    </div>
</div>

<?php Modal::end(); ?>
