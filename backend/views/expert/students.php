<?php

/** @var yii\web\View $this */
/** @var yii\bootstrap5\ActiveForm $form */

/** @var app\models\StudentsEvents $model */
/** @var app\models\StudentsEvents $dataProvider */

use common\widgets\Alert;
use yii\helpers\Html;
use yii\web\YiiAsset;
use yii\widgets\Pjax;

$this->title = 'Студенты';

$this->registerJsFile('@web/js/students.js', ['depends' => YiiAsset::class]);

?>

<div class="row">
    <?php Pjax::begin([
        'id' => 'pjax-add-student',
        'enablePushState' => false,
        'timeout' => 10000,
    ]); ?>
        <?= $this->render('_student-form', [
            'model' => $model
        ]) ?>
    <?php Pjax::end(); ?>
</div>

<div class="row">

    <?php Pjax::begin([
        'id' => 'pjax-students',
        'enablePushState' => false,
        'timeout' => 10000,
        'linkSelector' => false,
    ]); ?>
        <?= $this->render('_students-list', [
            'dataProvider' => $dataProvider
        ]) ?>
    <?php Pjax::end(); ?>
</div>
