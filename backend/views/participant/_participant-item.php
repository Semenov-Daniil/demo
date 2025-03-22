<?php

use yii\bootstrap5\Html;
use yii\bootstrap5\LinkPager;
use yii\grid\ActionColumn;
use yii\grid\GridView;
use yii\widgets\ListView;

/** @var common\models\Students $model */

?>

<div class="card mb-0">
    <div class="card-body">
        <h5 class="card-title"><?= Html::encode($model['fullName']); ?></h5>
        <ul class="list-group list-group-flush">
            <li class="list-group-item"><b>Логин:</b> <?= Html::encode($model['login']); ?></li>
            <li class="list-group-item"><b>Пароль:</b> <?= Html::encode($model['password']); ?></li>
        </ul>
        <?= Html::a('Скачать архив', ['/download-archive', 'student' => $model['students_id']], ['class' => 'btn btn-info', 'data' => ['pjax' => 0]])?>
    </div>
</div>
