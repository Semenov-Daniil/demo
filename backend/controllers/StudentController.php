<?php

namespace backend\controllers;

use common\models\EncryptedPasswords;
use common\models\Events;
use common\models\Students;
use PhpOffice\PhpWord\TemplateProcessor;
use Yii;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\helpers\VarDumper;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\Response;

class StudentController extends Controller
{
    public $defaultAction = 'students';

    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    [
                        'allow' => true,
                        'roles' => ['expert'],
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'students' => ['GET'],
                    'create-student' => ['POST'],
                    'all-students' => ['GET'],
                    'update-student' => ['GET', 'PATCH'],
                    'delete-students' => ['DELETE'],
                    'export-students' => ['GET'],
                ],
            ],
        ];
    }

    /**
     * Displays students page.
     *
     * @return string
     */
    public function actionStudents(?int $event = null): string
    {
        $model = new Students(['scenario' => Students::SCENARIO_CREATE, 'events_id' => $event]);
        $dataProvider = $model->getDataProviderStudents($event);

        return $this->render('students', [
            'model' => $model,
            'dataProvider' => $dataProvider,
            'events' => Yii::$app->user->can('sExpert') ? Events::getExpertEvents() : Events::getEvents(Yii::$app->user->id),
            'event' => Events::findOne(['id' => $event])
        ]);
    }

    public function actionCreateStudent(): string
    {
        $model = new Students(['scenario' => Students::SCENARIO_CREATE]);

        if ($this->request->isPost) {
            $data = Yii::$app->request->post();

            if ($model->load($data) && $model->createStudent()) {
                Yii::$app->session->addFlash('toastify', [
                    'text' => 'Студент успешно добавлен.',
                    'type' => 'success'
                ]);
                $model = new Students(['scenario' => Students::SCENARIO_CREATE, 'events_id' => $model->events_id]);
            } else {
                Yii::$app->session->addFlash('toastify', [
                    'text' => 'Не удалось добавить студента.',
                    'type' => 'error'
                ]);
            }
        }

        if ($this->request->isAjax) {
            return $this->renderAjax('_student-create', [
                'model' => $model,
                'events' => Yii::$app->user->can('sExpert') ? Events::getExpertEvents() : Events::getEvents(Yii::$app->user->id),
            ]);
        }

        return $this->render('_student-create', [
            'model' => $model,
            'events' => Yii::$app->user->can('sExpert') ? Events::getExpertEvents() : Events::getEvents(Yii::$app->user->id),
        ]);
    }

    public function actionAllStudents(?string $event = null): string
    {
        $dataProvider = Students::getDataProviderStudents($event);
        $modelEvent = Events::findOne(['id' => $event]);

        session_write_close();

        if ($this->request->isAjax) {
            return $this->renderAjax('_students-list', [
                'dataProvider' => $dataProvider,
                'event' => $modelEvent
            ]);
        }

        return $this->render('_students-list', [
            'dataProvider' => $dataProvider,
            'event' => $modelEvent
        ]);
    }

    public function actionUpdateStudent(?string $id = null): Response|string
    {
        $model = $this->findStudent($id);
        $model->scenario = Students::SCENARIO_UPDATE;

        if ($this->request->isPatch) {
            if ($model->load($this->request->post()) && $model->updateStudent($id)) {
                Yii::$app->session->addFlash('toastify', [
                    'text' => 'Студент успешно обновлен.',
                    'type' => 'success'
                ]);

                return $this->asJson([
                    'success' => true
                ]);
            } else {
                Yii::$app->session->addFlash('toastify', [
                    'text' => 'Не удалось обновить студента.',
                    'type' => 'error'
                ]);
            }
        }

        if ($this->request->isAjax) {
            return $this->renderAjax('_student-update', [
                'model' => $model,
            ]);
        }

        return $this->render('_student-update', [
            'model' => $model,
        ]);
    }

    /**
     * Action delete students.
     * 
     * @param string|null $id student ID. 
     *
     * @return void
     */
    public function actionDeleteStudents(?string $id = null): Response|string
    {
        $dataProvider = Students::getDataProviderStudents(10);
        $students = [];
        $result = [];

        $students = (!is_null($id) ? [$id] : ($this->request->post('students') ? $this->request->post('students') : []));

        if (count($students) && $result['success'] = Students::deleteStudents($students)) {
            $result['message'] = 'Students delete.';
            Yii::$app->session->addFlash('toastify', [
                'text' => count($students) > 1 ? 'Студенты успешно удалены.' : 'Студент успешно удален.',
                'type' => 'success'
            ]);
        } else {
            $result['message'] = 'Students not delete.';
            Yii::$app->session->addFlash('toastify', [
                'text' => count($students) > 1 ? 'Не удалось удалить студентов.' : 'Не удалось удалить студента.',
                'type' => 'error'
            ]);
        }

        $result['code'] = Yii::$app->response->statusCode;

        return $this->asJson([
            'data' => $result
        ]);
    }

    public function actionExportStudents(?string $event = null)
    {
        $students = Students::getExportStudents($event);
        $templatePath = Yii::getAlias('@templates/template.docx');

        $templateProcessor = new TemplateProcessor($templatePath);

        $templateProcessor->cloneBlock('block_student', count($students), true, true);

        foreach ($students as $index => $student) {
            $blockIndex = $index + 1;

            $templateProcessor->setValue("fio#{$blockIndex}", $student['fullName']);
            $templateProcessor->setValue("login#{$blockIndex}", $student['login']);
            $templateProcessor->setValue("password#{$blockIndex}", EncryptedPasswords::decryptByPassword($student['encrypted_password']));

            $templateProcessor->setValue("web#{$blockIndex}", $this->request->getHostInfo());
        }

        $filename = 'students_' . date('d-m-Y') . '.docx';

        header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: max-age=0');

        try {
            $templateProcessor->saveAs('php://output');
        } catch (\Exception $e) {
            Yii::error('Ошибка при экспорте участников: ' . $e->getMessage());
            throw new \yii\web\HttpException(500, 'Ошибка при генерации документа.');
        }

        exit;
    }

    protected function findStudent($id)
    {
        if (($model = Students::findStudent($id)) !== null) {
            return $model;
        }

        Yii::$app->session->addFlash('toastify', [
            'text' => "Студент не найден.",
            'type' => 'error'
        ]);

        throw new NotFoundHttpException('Студент не найден.');
    }
}
