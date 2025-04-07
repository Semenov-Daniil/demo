<?php

use backend\assets\AppAsset as BackendAppAsset;
use common\assets\ChoicesAsset;
use common\assets\DropzoneAsset;
use yii\widgets\Pjax;

/** @var yii\web\View $this */
/** @var yii\bootstrap5\ActiveForm $form */
/** @var app\models\FilesEvents $model */
/** @var array $event */
/** @var array $directories */

$this->registerJsFile('@web/js/modules/file/filesUpload.js', ['depends' => [BackendAppAsset::class, DropzoneAsset::class, ChoicesAsset::class]], 'filesUpload');

?>

<div>
    <div class="card">
        <div class="card-body">
            <div class="dropzone">
                <div class="dz-message needsclick">
                    <div class="mb-3">
                        <i class="display-4 text-muted ri-upload-cloud-2-fill"></i>
                    </div>
                    <h4>Перетащите файлы или нажмите, чтобы загрузить.</h4>
                </div>
            </div>
            <?php Pjax::begin([
                'id' => 'pjax-upload-form',
                'enablePushState' => false,
                'timeout' => 10000,
            ]); ?>
                <?= $this->render('_files-upload-form', [
                    'model' => $model,
                    'events' => $events,
                    'directories' => $directories,
                ]); ?>
            <?php Pjax::end(); ?>
            <ul class="list-unstyled mb-0" id="dropzone-preview">
                <li class="mt-2 dz-preview-item dropzone-preview-list d-none">
                    <div class="border rounded">
                        <div class="d-flex p-2">
                            <div class="d-flex flex-shrink-0 align-items-center me-3">
                                <div class="d-flex align-items-center justify-content-center avatar-sm preview-img bg-light rounded">
                                    <i class="display-6 text-muted ri-file-3-fill"></i>
                                </div>
                            </div>
                            <div class="flex-grow-1">
                                <div class="pt-1">
                                    <h5 class="fs-14 mb-1" data-dz-name>&nbsp;</h5>
                                    <p class="fs-13 text-muted mb-0" data-dz-size></p>
                                    <strong class="error text-danger" data-dz-errormessage></strong>
                                    <div class="dz-progress progress progress-sm mt-1 d-none">
                                        <div class="dz-upload progress-bar animated-progress bg-success" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100" data-dz-uploadprogress></div>
                                    </div>
                                </div>
                            </div>
                            <div class="flex-shrink-0 ms-3 d-flex align-items-center">
                                <button data-dz-remove class="btn btn-icon btn-soft-danger btn-danger"><i class="ri-close-circle-line fs-18"></i></button>
                            </div>
                        </div>
                    </div>
                </li>
            </ul>
        </div>
    </div>
</div>
