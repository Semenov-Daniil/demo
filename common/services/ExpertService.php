<?php

namespace common\services;

use common\models\Events;
use common\models\ExpertForm;
use common\models\Experts;
use common\models\Students;
use common\models\Users;
use Exception;
use Yii;
use yii\helpers\VarDumper;

class ExpertService
{
    private $userService;
    private $studentService;

    public function __construct()
    {
        $this->userService = new UserService();
        $this->studentService = new StudentService();
    }

    /**
     * Creates a new expert from Experts model data.
     * @param Experts $expertModel
     * @return bool
     * @throws Exception
     */
    public function createExpert(ExpertForm $expertModel): bool
    {
        if (!$expertModel->validate()) {
            return false;
        }

        $transaction = Yii::$app->db->beginTransaction();
        try {
            $user = $this->userService->createBaseUser(Experts::TITLE_ROLE_EXPERT, [
                'surname' => $expertModel->surname,
                'name' => $expertModel->name,
                'patronymic' => $expertModel->patronymic,
            ]);

            $transaction->commit();
            return true;
        } catch (Exception $e) {
            $transaction->rollBack();
            $this->userService->deleteUser($user->id ?? null);
            VarDumper::dump($e, 10, true);die;
        }
    }

    /**
     * Updates an existing expert.
     * @param int $id
     * @param Experts $expertModel
     * @return bool
     */
    public function updateExpert(int $id, ExpertForm $expertModel): bool
    {
        if (!$expertModel->validate()) {
            return false;
        }

        $user = Users::findOne($id);
        if ($user) {
            $user->surname = $expertModel->surname;
            $user->name = $expertModel->name;
            $user->patronymic = $expertModel->patronymic;
            return $user->save();
        }
        return false;
    }

    /**
     * Deletes multiple experts.
     * @param array $expertIds
     * @return bool
     */
    public function deleteExperts(array $expertIds): bool
    {
        foreach ($expertIds as $id) {
            if (!$this->deleteExpert($id)) {
                return false;
            }
        }
        return true;
    }

    private function deleteExpert(?int $id): bool
    {
        $user = Users::findOne($id);
        if (!$user || $user->id === Yii::$app->user->id) {
            return false;
        }

        $transaction = Yii::$app->db->beginTransaction();
        try {
            $eventIds = array_column($user->events, 'id');
            $studentIds = Students::find()->select('id')->where(['events_id' => $eventIds])->asArray()->all();
            
            $this->studentService->deleteStudents($studentIds);
            Events::removeDirectory($eventIds);
    
            return $this->userService->deleteUser($id);
        } catch (Exception $e) {
            $transaction->rollBack();
            throw $e;
        }

       
    }
}