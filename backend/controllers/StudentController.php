<?php

namespace backend\controllers;

use common\models\EncryptedPasswords;
use common\models\Events;
use common\models\StudentForm;
use common\models\Students;
use common\models\Users;
use common\services\StudentService;
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
        $model = new StudentForm(['scenario' => StudentForm::SCENARIO_CREATE, 'events_id' => $event]);
        $dataProvider = Students::getDataProviderStudents($event);

        return $this->render('students', [
            'model' => $model,
            'dataProvider' => $dataProvider,
            'events' => Yii::$app->user->can('sExpert') ? Events::getExpertEvents() : Events::getEvents(Yii::$app->user->id),
            'event' => Events::findOne(['id' => $event])
        ]);
    }

    public function actionCreateStudent(): string
    {
        $form = new StudentForm(['scenario' => StudentForm::SCENARIO_CREATE]);
        $service = new StudentService();

        if ($this->request->isPost && $form->load(Yii::$app->request->post()) && $service->createStudent($form)) {
            Yii::$app->session->addFlash('toastify', [
                'text' => 'Студент успешно добавлен.',
                'type' => 'success'
            ]);
            $form = new StudentForm(['scenario' => StudentForm::SCENARIO_CREATE, 'events_id' => $form->events_id]);
        } else {
            Yii::$app->session->addFlash('toastify', [
                'text' => 'Не удалось добавить студента.',
                'type' => 'error'
            ]);
        }

        Yii::$app->session->close();

        return $this->request->isAjax 
            ? $this->renderAjax('_student-create', [
                'model' => $form,
                'events' => Yii::$app->user->can('sExpert') ? Events::getExpertEvents() : Events::getEvents(Yii::$app->user->id),
            ])
            : $this->render('_student-create', [
                'model' => $form,
                'events' => Yii::$app->user->can('sExpert') ? Events::getExpertEvents() : Events::getEvents(Yii::$app->user->id),
            ]);
    }

    public function actionAllStudents(?int $event = null): string
    {
        $dataProvider = Students::getDataProviderStudents($event);
        $modelEvent = Events::findOne(['id' => $event]);

        session_write_close();

        return $this->request->isAjax 
            ? $this->renderAjax('_students-list', ['dataProvider' => $dataProvider, 'event' => $modelEvent])
            : $this->render('_students-list', ['dataProvider' => $dataProvider, 'event' => $modelEvent]);
    }

    public function actionUpdateStudent(?int $id = null): Response|string
    {
        $form = $this->findStudentForm($id);
        $form->scenario = StudentForm::SCENARIO_UPDATE;
        $service = new StudentService();

        if ($this->request->isPatch && $form->load($this->request->post()) && $service->updateStudent($id, $form)) {
            Yii::$app->session->addFlash('toastify', [
                'text' => 'Студент успешно обновлен.',
                'type' => 'success'
            ]);
            Yii::$app->session->close();
            return $this->asJson(['success' => true]);
        } else {
            Yii::$app->session->addFlash('toastify', [
                'text' => 'Не удалось обновить студента.',
                'type' => 'error'
            ]);
        }

        Yii::$app->session->close();

        return $this->request->isAjax 
            ? $this->renderAjax('_student-update', ['model' => $form])
            : $this->render('_student-update', ['model' => $form]);
    }

    /**
     * Action delete students.
     * 
     * @param string|null $id student ID. 
     *
     * @return void
     */
    public function actionDeleteStudents(?int $id = null): Response|string
    {
        $service = new StudentService();
        $students = $id ? [$id] : ($this->request->post('students') ?: []);
        $result = [];

        if ($students && $result['success'] = $service->deleteStudents($students)) {
            $result['message'] = 'Students deleted.';
            Yii::$app->session->addFlash('toastify', [
                'text' => count($students) > 1 ? 'Студенты успешно удалены.' : 'Студент успешно удален.',
                'type' => 'success'
            ]);
            Yii::$app->session->close();
        } else {
            $result['message'] = 'Students not deleted.';
            Yii::$app->session->addFlash('toastify', [
                'text' => count($students) > 1 ? 'Не удалось удалить студентов.' : 'Не удалось удалить студента.',
                'type' => 'error'
            ]);
            Yii::$app->session->close();
        }

        $result['code'] = Yii::$app->response->statusCode;
        return $this->asJson(['data' => $result]);
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

    protected function findStudentForm(?string $id): StudentForm
    {
        if ($user = Users::findOne($id)) {
            $form = new StudentForm();
            $form->surname = $user->surname;
            $form->name = $user->name;
            $form->patronymic = $user->patronymic;
            return $form;
        }

        Yii::$app->session->addFlash('toastify', [
            'text' => "Студент не найден.",
            'type' => 'error'
        ]);
        throw new NotFoundHttpException('Студент не найден.');
    }
}
