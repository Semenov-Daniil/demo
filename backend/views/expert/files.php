<?php

/** @var yii\web\View $this */
/** @var app\models\FilesEvents $model */
/** @var app\models\FilesEvents $dataProvider */

use common\assets\AppAsset;
use common\assets\DropzoneAsset;
use common\widgets\Alert;
use yii\bootstrap5\Html;
use yii\web\View;
use yii\web\YiiAsset;
use yii\widgets\Pjax;

DropzoneAsset::register($this);

$this->title = 'Файлы';

$options = [
    'maxFileSize' => Yii::$app->fileComponent->getMaxSizeFiles('m'),
];

$this->registerJs(
    "const yiiOptions = ".\yii\helpers\Json::htmlEncode($options).";",
    View::POS_HEAD,
    'yiiOptions'
);

$this->registerJsFile('@web/js/files.js', ['depends' => AppAsset::class]);
$this->registerJsFile('@web/js/pages/form-file-upload.init.js', ['depends' => DropzoneAsset::class]);

?>

<div class="row">
    <?php Pjax::begin([
        'id' => 'pjax-upload-files',
        'enablePushState' => false,
        'timeout' => 10000,
    ])?>
        <?= $this->render('_files-form', [
            'model' => $model
        ]); ?>
    <?php Pjax::end(); ?>
</div>

<div class="row">
    <?php Pjax::begin([
        'id' => 'pjax-files',
        'enablePushState' => false,
        'timeout' => 10000,
        'options' => [
            'data' => [
                'pjax-grid' => true
            ]
        ]
    ]); ?>
        <?= $this->render('_files-list', [
            'dataProvider' => $dataProvider
        ]); ?>
    <?php Pjax::end(); ?>
</div>
