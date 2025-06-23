<?php

/** @var yii\web\View $this */
/** @var common\models\Students $student */
/** @var yii\data\ActiveDataProvider $files */
/** @var array common\models\Modules $files */

use common\models\EncryptedPasswords;
use yii\grid\ActionColumn;
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
                                    <h2 class="mb-0 text-break"><?= Html::encode(Yii::$app->user->identity->fullName); ?></h2>
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

<div class="row row-cols-xl-2 row-cols-1">
    <div class="col">
        <div class="col">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title mb-0 flex-grow-1">Подключение диска</h4>
                </div>
                <div class="card-body">
                    <!-- <pre class="language-markup" tabindex="0"><code class="language-markup"></code></pre> -->
                    <pre class="language-markup"><code class="language-markup"><span>net use Y: \\<?= $_SERVER['SERVER_ADDR'] ?>\<?= Html::encode(Yii::$app->user->identity->login); ?> <?= Html::encode(EncryptedPasswords::decryptByPassword(Yii::$app->user->identity->encryptedPassword->encrypted_password)); ?> /user:<?= Html::encode(Yii::$app->user->identity->login); ?></span></code></pre>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title mb-0 flex-grow-1">Подключение SSH</h4>
                </div>
                
                <div class="card-body">
                    <!-- <pre class="language-markup" tabindex="0"><code class="language-markup"></code></pre> -->
                    <pre class="language-markup"><code class="language-markup">ssh <?= Html::encode(Yii::$app->user->identity->login); ?>@<?= $_SERVER['SERVER_ADDR'] ?></code></pre>
                </div>
            </div>
        </div>
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
                        'showHeader' => false,
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
                                    $file = $model->path;
                                    $fileSize = file_exists($file) ? filesize($file) : null;
                                    return '<span class="fs-14 mb-1 h5">'. "{$model->name}.{$model->extension}" .'</span>' 
                                            . (is_null($fileSize) ? '' : '<p class="fs-13 text-muted mb-0">' . Yii::$app->fileComponent->formatSizeUnits($fileSize)) . '</p>';
                                },
                                'options' => [
                                    'class' => 'col-4'
                                ],
                                'visible' => $files->totalCount,
                            ],
                            [
                                'label' => 'Расположение',
                                'value' => function ($model) {
                                    return $model->moduleTitle;
                                },
                                'options' => [
                                    'class' => 'col-4'
                                ],
                                // 'headerOptions' => [
                                //     'class' => 'text-center'
                                // ],
                                'contentOptions' => [
                                    'class' => 'text-center'
                                ],
                                'visible' => $files->totalCount,
                            ],
                            [
                                'class' => ActionColumn::class,
                                'template' => '<div class="d-flex flex-wrap gap-2 justify-content-end">{download}</div>',
                                'buttons' => [
                                    'download' => function ($url, $model, $key) {
                                        return Html::a('<i class="ri-download-2-line"></i>', ["download/{$model->id}"], ['class' => 'btn btn-icon btn-soft-secondary', 'data' => ['pjax' => 0]]);
                                    },
                                ],
                                'visible' => $files->totalCount,
                            ],
                        ],
                    ]); ?>
                </div>
            </div>
        </div>
    </div>
    <div class="col">
        <div class="col">
            <div class="card">
                <div class="card-header align-items-center d-flex position-relative">
                    <h4 class="card-title mb-0 flex-grow-1">Модули</h4>
                </div>
    
                <div class="card-body list-group p-0 list-group-flush">
                    <?php foreach ($modules as $module): ?>
                        <?= Html::a("<b>Модуль {$module['number']}:</b> http://{$module['domain']}", "http://{$module['domain']}", ['class' => 'list-group-item list-group-item-action'])?>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card">
                <div class="card-header align-items-center d-flex position-relative">
                    <h4 class="card-title mb-0 flex-grow-1">Веб-ресурсы</h4>
                </div>
    
                <div class="card-body list-group p-0 list-group-flush">
                    <?= Html::a("<b>phpMyAdmin:</b> http://{$_SERVER['SERVER_ADDR']}/phpmyadmin", "http://{$_SERVER['SERVER_ADDR']}/phpmyadmin/index.php?user=".Yii::$app->user->identity->login."&password=".EncryptedPasswords::decryptByPassword(Yii::$app->user->identity->encryptedPassword->encrypted_password), ['class' => 'list-group-item list-group-item-action'])?>
                </div>
            </div>
        </div>
    </div>
</div>

