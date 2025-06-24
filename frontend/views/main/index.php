<?php

/** @var yii\web\View $this */
/** @var common\models\Students $student */
/** @var yii\data\ActiveDataProvider $files */

use common\assets\AppAsset;
use common\models\EncryptedPasswords;
use yii\grid\ActionColumn;
use yii\grid\GridView;
use yii\helpers\Html;

$this->title = 'Студент';

$this->registerJsFile('js/sseDataUpdates.js', ['depends' => AppAsset::class]);

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
            <?= $this->render('_files-list', [
                'files' => $files
            ]); ?>
        </div>
    </div>
    <div class="col">
        <div class="col">
            <?= $this->render('_modules-list', [
                'modules' => $modules
            ]); ?>
        </div>
        <div class="col">
            <div class="card">
                <div class="card-header align-items-center d-flex position-relative">
                    <h4 class="card-title mb-0 flex-grow-1">Веб-ресурсы</h4>
                </div>
    
                <div class="card-body list-group p-0 list-group-flush">
                    <?= Html::a("<b>phpMyAdmin:</b> http://{$_SERVER['HTTP_HOST']}/phpmyadmin", "http://{$_SERVER['HTTP_HOST']}/phpmyadmin/?user=".Yii::$app->user->identity->login."&pass=".EncryptedPasswords::decryptByPassword(Yii::$app->user->identity->encryptedPassword->encrypted_password), ['class' => 'list-group-item list-group-item-action', 'target' => '_open'])?>
                </div>
            </div>
        </div>
    </div>
</div>

