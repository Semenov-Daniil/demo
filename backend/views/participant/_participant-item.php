<?php

use common\models\Students;
use yii\bootstrap5\Html;
use yii\bootstrap5\LinkPager;
use yii\grid\ActionColumn;
use yii\grid\GridView;
use yii\widgets\ListView;

/** @var common\models\Students $model */

?>

<div class="card mb-0">
    <div class="card-body">
        <div class="d-flex align-items-center">
            <h5 class="card-title m-0 col"><?= Html::encode($model['fullName']); ?></h5>
            <?= Html::button('<i class="ri-more-fill align-middle"></i>', ['class' => 'btn btn-soft-secondary btn-sm col-auto', 'data' => ['bs-toggle' => 'dropdown'], 'aria' => ['expanded' => 'false']]); ?>
            <?= Html::ul([
                Html::tag('span', 'Скачать архив:', ['class' => 'dropdown-header']),
                Html::a('Все модули', ['/download-archive', 'student' => $model['students_id']], ['class' => 'dropdown-item', 'data' => ['pjax' => 0]]),
                ...array_map(fn($directory) => Html::a('Модуль ' . $directory['number'], ['/download-archive', 'student' => $model['students_id'], 'folderTitle' => $directory['title']], ['class' => 'dropdown-item', 'data' => ['pjax' => 0]]), $model['directories'])
            ], ['class' => 'dropdown-menu', 'encode' => false]); ?>
        </div>
        <ul class="list-group list-group-flush">
            <li class="list-group-item"><b>Логин:</b> <?= Html::encode($model['login']); ?></li>
            <li class="list-group-item"><b>Пароль:</b> <?= Html::encode($model['password']); ?></li>
        </ul>
        <?= Html::a('Скачать архив', ['/download-archive', 'student' => $model['students_id']], ['class' => 'btn btn-info', 'data' => ['pjax' => 0]])?>
    </div>
</div>
