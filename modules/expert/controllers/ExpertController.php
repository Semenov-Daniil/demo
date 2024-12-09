<?php

namespace app\modules\expert\controllers;

use app\models\ExpertsEvents;
use app\models\FilesEvents;
use app\models\Modules;
use app\models\StudentsEvents;
use Yii;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\web\Controller;
use yii\web\UploadedFile;

class ExpertController extends Controller
{
    public $defaultAction = 'experts';

    public function behaviors()
    {
        return [
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'delete-experts' => ['DELETE'],
                    'delete-students' => ['DELETE'],
                    'change-status-modules' => ['PATH'],
                    'delete-modules' => ['DELETE'],
                ],
            ],
        ];
    }

    /**
     * Displays experts page.
     *
     * @return string
     */
    public function actionExperts(): string
    {
        $model = new ExpertsEvents();

        if (Yii::$app->request->isAjax && !is_null(Yii::$app->request->post('add'))) {
            if ($model->load(Yii::$app->request->post()) && $model->addExpert()) {
                Yii::$app->session->setFlash('success', "Эксперт успешно добавлен.");
                $model = new ExpertsEvents();
            } else {
                Yii::$app->session->setFlash('error', "Не удалось добавить эксперта.");
            }
        }

        $dataProvider = $model->getDataProviderExperts(20);

        return $this->render('experts', [
            'model' => $model,
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * Find experts info.
     */
    public function actionInfoExperts()
    {
        if (Yii::$app->request->isAjax) {
            $model = new ExpertsEvents();
            return $this->renderAjax('experts', [
                'model' => $model,
                'dataProvider' => $model->getDataProviderExperts(20),
            ]);
        }
    }

    /**
     * Action delete experts.
     *
     * @param string $id expert ID. 
     * 
     * @return void
     */
    public function actionDeleteExperts(string $id): void
    {
        if (Yii::$app->request->isAjax) {
            if (ExpertsEvents::deleteExpert($id)) {
                Yii::$app->session->setFlash('info', "Эксперт успешно удален.");
            } else {
                Yii::$app->session->setFlash('error', "Не удалось удалить эксперта.");
            }
        }
    }

    /**
     * Displays students page.
     *
     * @return string
     */
    public function actionStudents(): string
    {
        $model = new StudentsEvents(['scenario' => StudentsEvents::SCENARIO_ADD_STUDENT]);

        if (Yii::$app->request->isAjax && !is_null(Yii::$app->request->post('add'))) {
            if ($model->load(Yii::$app->request->post()) && $model->addStudent()) {
                Yii::$app->session->setFlash('success', "Студент успешно добавлен.");
                $model = new StudentsEvents(['scenario' => StudentsEvents::SCENARIO_ADD_STUDENT]);
            } else {
                Yii::$app->session->setFlash('error', "Не удалось добавить студента.");
            }

            return $this->renderAjax('students', [
                'model' => $model,
            ]);
        }

        return $this->render('students', [
            'model' => $model,
            'dataProvider' => $model->getDataProviderStudents(20),
        ]);
    }

    /**
     * Find students info.
     */
    public function actionInfoStudents()
    {
        if (Yii::$app->request->isAjax) {
            $model = new StudentsEvents(['scenario' => StudentsEvents::SCENARIO_ADD_STUDENT]);
            return $this->renderAjax('students', [
                'model' => $model,
                'dataProvider' => $model->getDataProviderStudents(20),
            ]);
        }
    }

    /**
     * Action delete students.
     * 
     * @param string|null $id student ID. 
     *
     * @return void
     */
    public function actionDeleteStudents(string|null $id = null): void
    {
        if (Yii::$app->request->isAjax) {
            if (StudentsEvents::deleteStudent($id)) {
                // Yii::$app->session->setFlash('success', "Студент успешно удален.");
            } else {
                // Yii::$app->session->setFlash('error', "Не удалось удалить студента.");
            }
        }
    }

    /**
     * Displays files page.
     *
     * @return string
     */
    public function actionFiles()
    {
        $model = new FilesEvents(['scenario' => FilesEvents::SCENARIO_UPLOAD_FILE]);

        if (Yii::$app->request->isAjax && !is_null(Yii::$app->request->post('add'))) {
            $model->files = UploadedFile::getInstances($model, 'files');

            if (!empty($model->files) && $model->uploadFiles()) {
                Yii::$app->session->setFlash('success', "Файл(-ы) успешно добавлен(-ы).");
            } else {
                Yii::$app->session->setFlash('error', "Не удалось добавить файл(-ы).");
            }

            return $this->renderAjax('files', [
                'model' => $model,
            ]);
        }

        return $this->render('files', [
            'model' => $model,
            'dataProvider' => $model->getDataProviderFiles(20),
        ]);
    }

    /**
     * Find files info.
     *
     * @return string
     */
    public function actionInfoFiles()
    {
        if (Yii::$app->request->isAjax) {
            $model = new FilesEvents();
            return $this->renderAjax('files', [
                'model' => $model,
                'dataProvider' => $model->getDataProviderFiles(20),
            ]);
        }
    }

    /**
     * Action delete students.
     * 
     * @param string|null $id student ID. 
     *
     * @return void
     */
    public function actionDeleteFiles(string|null $id = null): void
    {
        if (Yii::$app->request->isAjax) {
            if (FilesEvents::deleteFileEvent($id)) {
                // Yii::$app->session->setFlash('success', "Файл успешно удален.");
            } else {
                // Yii::$app->session->setFlash('error', "Не удалось удалить файл.");
            }
        }
    }

    /**
     * Displays modules page.
     *
     * @return string
     */
    public function actionModules(): string
    {
        return $this->render('modules', [
            'dataProvider' => Modules::getDataProviderModules(),
        ]);
    }

    /**
     * Action change status modules.
     */
    public function actionChangeStatusModules()
    {
        if (Yii::$app->request->isAjax) {
            $model = new Modules();
            if ($model->load(Yii::$app->request->post(), '')) {
                if ($model->changeStatus()) {
                    Yii::$app->response->statusCode = 200;
                    return $this->asJson(['status' => $model->status]);
                } else {
                    Yii::$app->response->statusCode = 500;
                    return $this->asJson(['status' => !$model->status]);
                }
            }
        }
    }

    /**
     * Action delete modules.
     * 
     * @param string|null $id module ID. 
     *
     * @return void
     */
    public function actionDeleteModules(string|null $id = null): void
    {
        if (Yii::$app->request->isAjax) {
            if (Modules::deleteModule($id)) {
                // Yii::$app->session->setFlash('success', "Модуль успешно удален.");
            } else {
                // Yii::$app->session->setFlash('error', "Не удалось удалить модуль.");
            }
        }
    }

    /**
     * Displays competitors page.
     *
     * @return string
     */
    public function actionCompetitors()
    {
        return $this->render('competitors');
    }
}
