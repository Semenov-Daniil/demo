<?php

/** @var yii\web\View $this */
/** @var yii\data\ActiveDataProvider $files */

use common\models\EncryptedPasswords;
use yii\grid\GridView;
use yii\helpers\Html;

$this->title = 'Студент';

?>

<div class="row">
    <div class="col-xl-12">
        <div class="card crm-widget">
            <div class="card-body p-0">
                <div class="row row-cols-md-3 row-cols-1 g-0">
                    <div class="col overflow-auto">
                        <div class="py-4 px-3">
                            <h5 class="text-muted text-uppercase fs-13">ФИО</h5>
                            <div class="d-flex align-items-center">
                                <div class="flex-grow-1">
                                    <h2 class="mb-0 cfs-22"><?= Html::encode(Yii::$app->user->identity->fullName); ?></h2>
                                </div>
                            </div>
                        </div>
                    </div><!-- end col -->
                    <div class="col overflow-auto">
                        <div class="py-4 px-3">
                            <h5 class="text-muted text-uppercase fs-13">Логин</h5>
                            <div class="d-flex align-items-center">
                                <div class="flex-grow-1">
                                    <h2 class="mb-0 cfs-22"><?= Html::encode(Yii::$app->user->identity->login); ?></h2>
                                </div>
                            </div>
                        </div>
                    </div><!-- end col -->
                    <div class="col overflow-auto">
                        <div class="py-4 px-3">
                            <h5 class="text-muted text-uppercase fs-13">Пароль</h5>
                            <div class="d-flex align-items-center">
                                <div class="flex-grow-1">
                                    <h2 class="mb-0 cfs-22"><?= Html::encode(EncryptedPasswords::decryptByPassword(Yii::$app->user->identity->encryptedPassword->encrypted_password)); ?> </h2>
                                </div>
                            </div>
                        </div>
                    </div><!-- end col -->
                </div><!-- end row -->
            </div><!-- end card body -->
        </div><!-- end card -->
    </div><!-- end col -->
</div>

<div class="row">
    <div class="col">
        <div class="card">
            <div class="card-header align-items-center d-flex position-relative border-bottom-0">
                <h4 class="card-title mb-0 flex-grow-1">Файлы</h4>
            </div>

            <div class="card-body">
                <?= GridView::widget([
                    'dataProvider' => $files,
                    'pager' => [
                        'class' => \yii\bootstrap5\LinkPager::class,
                        'listOptions' => [
                            'class' => 'pagination pagination-separated m-0',
                        ],
                        'maxButtonCount' => 5,
                        'prevPageLabel' => '<i class="ri-arrow-left-double-line"></i>',
                        'nextPageLabel' => '<i class="ri-arrow-right-double-line"></i>'
                    ],
                    'emptyText' => 'Ничего не найдено.',
                    'emptyTextOptions' => [
                        'class' => 'text-center',
                    ],
                    'tableOptions' => [
                        'class' => 'table align-middle table-nowrap table-hover table-borderless mb-0 border-bottom',
                    ],
                    'layout' => "
                        <div class=\"table-responsive table-card table-responsive\">
                            <div>
                                {items}
                            </div>
                            ". ($files->totalCount 
                            ? 
                                "
                                <div class=\"d-flex gap-2 flex-wrap justify-content-between align-items-center p-3 gridjs-pagination\">
                                    <div class=\"text-body-secondary\">
                                        {summary}
                                    </div>
                                    <div>
                                        {pager}
                                    </div>
                                </div>
                                "
                            : 
                                ''
                            )."
                        </div>
                    ",
                    'columns' => [
                        [
                            'label' => 'Файл',
                            'format' => 'raw',
                            'content' => function ($model) {
                                $file = Yii::getAlias('@events/' . $model['dir_title'] . '/' . $model['save_name'] . '.' . $model['extension']);
                                $fileSize = file_exists($file) ? filesize($file) : '';
                                return "
                                    <h5 class=\"fs-14 mb-1\">". $model['origin_name'] . '.' . $model['extension'] ."</h5>
                                    <p class=\"fs-13 text-muted mb-0\">". (empty($fileSize) ? '' : Yii::$app->fileComponent->formatSizeUnits($fileSize)) ."</p>
                                ";
                            },
                            'options' => [
                                'class' => 'col-6'
                            ],
                            'visible' => $files->totalCount,
                        ],
                    ],
                ]); ?>
            </div>
        </div>
    </div>
</div>

<div class="row row-cols-lg-3 row-cols-1">
    <div class="col">
        <div class="card">
            <div class="card-header align-items-center d-flex position-relative">
                <h4 class="card-title mb-0 flex-grow-1">Состояние</h4>
            </div>

            <div class="card-body">    
            </div>
        </div>
    </div>
    <div class="col">
        <div class="card">
            <div class="card-header align-items-center d-flex position-relative">
                <h4 class="card-title mb-0 flex-grow-1">Модули</h4>
            </div>

            <div class="card-body">
                
            </div>
        </div>
    </div>
    <div class="col">
        <div class="card">
            <div class="card-header align-items-center d-flex position-relative">
                <h4 class="card-title mb-0 flex-grow-1">Утилиты</h4>
            </div>

            <div class="card-body">
                
            </div>
        </div>
    </div>
</div>

