<?php

/** @var yii\web\View $this */
/** @var app\models\FilesEvents $model */
/** @var app\models\FilesEvents $dataProvider */

use common\widgets\Alert;
use yii\bootstrap5\Html;
use yii\widgets\Pjax;

$this->title = 'Файлы';

$this->registerCssFile('@common/web/libs/dropzone/dropzone.css');

$this->registerJsFile('@web/js/files.js', ['depends' => 'yii\web\JqueryAsset']);

$this->registerJsFile('@common/web/libs/dropzone/dropzone-min.js', ['depends' => 'yii\web\JqueryAsset']);
$this->registerJsFile('@common/web/js/pages/form-file-upload.init.js', ['depends' => 'yii\web\JqueryAsset']);

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
