<?php

/** @var yii\web\View $this */
/** @var yii\bootstrap5\ActiveForm $form */

/** @var app\models\StudentsEvents $model */
/** @var yii\data\ActiveDataProvider $dataProvider */
/** @var array $events */
/** @var int $event */

use backend\assets\AppAsset as BackendAppAsset;
use common\assets\AppAsset;
use common\assets\ChoicesAsset;
use common\models\Events;
use common\widgets\Alert;
use yii\bootstrap5\Modal;
use yii\helpers\Html;
use yii\web\YiiAsset;
use yii\widgets\Pjax;

ChoicesAsset::register($this);
BackendAppAsset::register($this);

$this->title = 'Студенты';

$this->registerJsFile('@web/js/modules/student/students.js', ['depends' => BackendAppAsset::class]);

?>

<div class="row">
    <div>
        <?= $this->render('_student-create', [
            'model' => $model,
            'events' => $events
        ]) ?>
    </div>
</div>

<div class="row">
    <?php Pjax::begin([
        'id' => 'pjax-students',
        'timeout' => 10000,
    ]); ?>
        <?= $this->render('_students-list', [
            'dataProvider' => $dataProvider,
            'model' => $model,
            'event' => $event
        ]) ?>
    <?php Pjax::end(); ?>
</div>

<?php Modal::begin([
    'id' => 'modal-update-student',
    'size' => Modal::SIZE_DEFAULT,
    'title' => 'Редактирование студента',
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
        <div class="mr-lg-3 placeholder col-5 mb-2 rounded-1"></div>
        <div class="form-control placeholder p-3"></div>
    </div>
    <div class="col-12 d-flex flex-wrap gap-2 justify-content-end">
        <button type="submit" class="btn btn-success disabled placeholder col-3"></button>
    </div>
</div>

<?php Modal::end(); ?>
