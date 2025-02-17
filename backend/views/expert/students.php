<?php

/** @var yii\web\View $this */
/** @var yii\bootstrap5\ActiveForm $form */

/** @var app\models\StudentsEvents $model */
/** @var app\models\StudentsEvents $dataProvider */

use common\widgets\Alert;
use yii\helpers\Html;
use yii\widgets\Pjax;

$this->title = 'Студенты';

$this->registerJsFile('@web/js/students.js', ['depends' => 'yii\web\JqueryAsset']);

?>
<div class="col-12 site-students">

    <?php Pjax::begin([
        'id' => 'pjax-students',
        'enablePushState' => false,
        'timeout' => 10000,
        'linkSelector' => false,
    ]); ?>
        <?= Alert::widget(); ?>
        
        <?= $this->render('_student-form', [
            'model' => $model
        ]) ?>

        <?= $this->render('_students-list', [
            'dataProvider' => $dataProvider
        ]) ?>
    <?php Pjax::end(); ?>
</div>
