<?php

/** @var yii\web\View $this */
/** @var app\models\FilesEvents $model */
/** @var app\models\FilesEvents $dataProvider */

use app\widgets\Alert;
use yii\bootstrap5\Html;
use yii\widgets\Pjax;

$this->title = 'Файлы';

$this->registerJsFile('/js/files.js', ['depends' => 'yii\web\JqueryAsset']);

?>
<div class="site-files">
    <h3><?= Html::encode($this->title) ?></h3>
    <div>
        <?php Pjax::begin([
            'id' => 'pjax-files',
            'enablePushState' => false,
            'timeout' => 10000,
        ]); ?>

            <?= Alert::widget(); ?>

            <?= $this->render('_files-form', [
                'model' => $model
            ]); ?>

            <?= $this->render('_files-list', [
                'dataProvider' => $dataProvider
            ]); ?>
        <?php Pjax::end(); ?>
    </div>
</div>
