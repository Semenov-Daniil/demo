<?php

/** @var yii\web\View $this */
/** @var string $name */
/** @var string $message */
/** @var Exception $exception */

use yii\helpers\Html;
use yii\helpers\Url;

$this->title = $name;
?>
<div class="auth-page-wrapper py-5 d-flex justify-content-center align-items-center">
    <div class="col-md-8 col-lg-6 col-xl-5">
        <div class="card mt-4 card-bg-fill">
    
            <div class="card-body p-4">
                <div class="text-center d-flex flex-column align-items-center">
                        <h1 class="mb-3"><?= Html::encode($this->title) ?></h1>
                        <div class="alert alert-danger text-break">
                            <?= nl2br(Html::encode($message)) ?>
                        </div>
                        <?= Html::a('<i class="ri-home-9-fill me-1"></i>Вернуться на главную', ['/'], ['class' => 'btn btn-success'])?>
                </div>
            </div>
            <!-- end card body -->
        </div>
        <!-- end card -->
    </div>
</div>
