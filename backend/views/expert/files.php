<?php

/** @var yii\web\View $this */
/** @var app\models\FilesEvents $model */
/** @var app\models\FilesEvents $dataProvider */

use common\widgets\Alert;
use yii\bootstrap5\Html;
use yii\web\YiiAsset;
use yii\widgets\Pjax;

$this->title = 'Файлы';

$this->registerCssFile('@web/libs/dropzone/dropzone.css');

$this->registerJsFile('@web/js/files.js', ['depends' => YiiAsset::class]);

$this->registerJsFile('@web/libs/dropzone/dropzone-min.js', ['depends' => YiiAsset::class]);
$this->registerJsFile('@web/js/pages/form-file-upload.init.js', ['depends' => YiiAsset::class]);

?>
<div class="site-files">
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
