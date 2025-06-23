<?php

/** @var yii\web\View $this */
/** @var common\models\Files $model */
/** @var common\models\FilesSearch $searchModel */
/** @var yii\data\ActiveDataProvider $dataProvider */
/** @var common\models\Events|null $event */
/** @var array $events */
/** @var array $directories */

use backend\assets\AppAsset as BackendAppAsset;
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
BackendAppAsset::register($this);

$this->title = 'Файлы';

$options = [
    'maxFileSize' => Yii::$app->fileComponent->getMaxSizeFiles('m'),
];

$this->registerJs(
    "const yiiOptions = ".\yii\helpers\Json::htmlEncode($options).";",
    View::POS_HEAD,
    'yiiOptions'
);

$this->registerJsFile('@web/js/modules/file/files.js', ['depends' => BackendAppAsset::class]);

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
        'enablePushState' => true,
        'timeout' => 10000
    ]); ?>
        <?= $this->render('_files-list', [
            'dataProvider' => $dataProvider,
            'event' => $event,
        ]); ?>
    <?php Pjax::end(); ?>
</div>
