<?php

/** @var yii\web\View $this */
/** @var common\models\Files $model */
/** @var common\models\Files $dataProvider */
/** @var common\models\Events|null $event */
/** @var array $events */
/** @var array $directories */

use common\assets\AppAsset;
use common\assets\ChoicesAsset;
use common\assets\DropzoneAsset;
use common\widgets\Alert;
use yii\bootstrap5\Html;
use yii\web\View;
use yii\web\YiiAsset;
use yii\widgets\Pjax;

ChoicesAsset::register($this);
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

$this->registerJsFile('@web/js/pages/dropzone.init.js', ['depends' => DropzoneAsset::class]);
$this->registerJsFile('@web/js/files.js', ['depends' => AppAsset::class]);

?>

<div class="row">
    <?= $this->render('_files-upload', [
        'model' => $model,
        'events' => $events,
        'directories' => $directories,
    ]); ?>
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
            'dataProvider' => $dataProvider,
            'event' => $event,
        ]); ?>
    <?php Pjax::end(); ?>
</div>
