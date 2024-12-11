<?php

/** @var yii\web\View $this */
/** @var yii\bootstrap5\ActiveForm $form */

/** @var app\models\StudentsEvents $model */
/** @var app\models\StudentsEvents $dataProvider */

use app\widgets\Alert;
use yii\helpers\Html;
use yii\widgets\Pjax;

$this->title = 'Студенты';

$this->registerJsFile('/js/students.js', ['depends' => 'yii\web\JqueryAsset']);

?>
<div class="site-students">

    <h3 class="mb-3"><?= Html::encode($this->title) ?></h3>
    
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
