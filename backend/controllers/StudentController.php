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
use yii\helpers\Html;
use yii\helpers\VarDumper;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\Response;

class StudentController extends BaseController
{
    private StudentService $studentService;

    public function __construct($id, $module, $config = [])
    {
        parent::__construct($id, $module, $config);
        $this->studentService = new StudentService();
    }

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
                    'list-students' => ['GET'],
                    'update-student' => ['GET', 'PATCH'],
                    'delete-students' => ['DELETE'],
                    'export-students' => ['GET'],
                ],
            ],
        ];
    }

    private function getEvents()
    {
        return Yii::$app->user->can('sExpert') ? Events::getExpertEvents() : Events::getEvents(Yii::$app->user->id);
    }

    /**
     * Displays students page.
     *
     * @return string
     */
    public function actionStudents(?int $event = null): string
    {
        return $this->render('students', [
            'model' => new StudentForm(['scenario' => StudentForm::SCENARIO_CREATE, 'events_id' => $event]),
            'dataProvider' => Students::getDataProviderStudents($event),
            'events' => $this->getEvents(),
            'event' => $this->findEvent($event)
        ]);
    }

    public function actionCreateStudent(): string
    {
        $form = new StudentForm(['scenario' => StudentForm::SCENARIO_CREATE]);

        if ($this->request->isPost && $form->load(Yii::$app->request->post())) {
            $success = $this->studentService->createStudent($form);

            $this->addFlashMessage(
                $success ? 'Студент успешно добавлен.' : 'Не удалось добавить студента.',
                $success ? 'success' : 'error'
            );

            if ($success) {
                $form = new StudentForm(['scenario' => StudentForm::SCENARIO_CREATE, 'events_id' => $form->events_id]);
            }
        }

        return $this->renderAjaxIfRequested('_student-create', ['model' => $form, 'events' => $this->getEvents()]);
    }

    public function actionListStudents(?int $event = null): string
    {
        return $this->renderAjaxIfRequested('_students-list', [
            'dataProvider' => Students::getDataProviderStudents($event), 
            'event' => $this->findEvent($event),
        ]);
    }

    public function actionUpdateStudent(?int $id = null): Response|string
    {
        $form = $this->findStudentForm($id);
        $form->scenario = StudentForm::SCENARIO_UPDATE;
        $result = ['success' => false];

        if ($this->request->isPatch && $form->load($this->request->post())) {
            $result['success'] = $this->studentService->updateStudent($id, $form);

            $this->addFlashMessage(
                $result['success'] ? 'Студент успешно обновлен.' : 'Не удалось обновить студента.',
                $result['success'] ? 'success' : 'error'
            );

            $result['errors'] = [];
            foreach ($form->getErrors() as $attribute => $errors) {
                $result['errors'][Html::getInputId($form, $attribute)] = $errors;
            }

            return $this->asJson($result);
        }

        return $this->renderAjaxIfRequested('_student-update', ['model' => $form]);
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
        $students = $id ? [$id] : ($this->request->post('students') ?: []);
        $count = count($students);
        $result = [];

        $result['success'] = $count && $this->studentService->deleteStudents($students);
        $result['message'] = $result['success'] ? 'Students deleted.' : 'Students not deleted.';

        $this->addFlashMessage(
            $result['success'] 
                ? ($count > 1 ? 'Студенты успешно удалены.' : 'Студент успешно удален.') 
                : ($count > 1 ? 'Не удалось удалить студентов.' : 'Не удалось удалить студента.'),
            $result['success'] ? 'success' : 'error'
        );

        $result['code'] = Yii::$app->response->statusCode;
        return $this->asJson($result);
    }

    public function actionExportStudents(?int $event = null)
    {
        try {
            $students = Students::getExportStudents($event);

            if (!$students) {
                return;
            }

            $templatePath = Yii::getAlias('@templates/template.docx');
            $templateProcessor = new TemplateProcessor($templatePath);

            $templateProcessor->cloneBlock('block_student', count($students), true, true);

            foreach ($students as $index => $student) {
                $blockIndex = $index + 1;
                $templateProcessor->setValue("fio#{$blockIndex}", $student['fullName']);
                $templateProcessor->setValue("login#{$blockIndex}", $student['login']);
                $templateProcessor->setValue("password#{$blockIndex}", $student['password']);
                $templateProcessor->setValue("web#{$blockIndex}", $this->request->getHostInfo());
            }

            $filename = 'students_' . Yii::$app->fileComponent->sanitizeFileName($this->findEvent($event)?->title) . '_' . date('d-m-Y') . '.docx';
            header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Cache-Control: max-age=0');

            $templateProcessor->saveAs('php://output');
        } catch (\Exception $e) {
            Yii::error('Ошибка при экспорте студентов: ' . $e->getMessage());
            $this->addFlashMessage('Не удалось экспортировать студентов', 'error');
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

    protected function findEvent(?int $id): ?Events
    {
        return Events::findOne(['id' => $id]);
    }
}
