<?php

namespace common\services;

use common\jobs\experts\DeleteExpert;
use common\jobs\experts\DeleteExpertEnvironment;
use common\models\Events;
use common\models\ExpertForm;
use common\models\Experts;
use common\models\Statuses;
use common\models\Students;
use common\models\Users;
use Exception;
use Yii;
use yii\db\ActiveRecord;
use yii\helpers\VarDumper;

class ExpertService
{
    private $userService;
    private $studentService;
    private $eventService;

    public function __construct()
    {
        $this->userService = new UserService();
        $this->studentService = new StudentService();
        $this->eventService = new EventService();
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

            $user->statuses_id = Statuses::getStatusId(Statuses::READY);
            if ($user->update() === false) throw "Failed to update the status of the expert";

            $transaction->commit();
            return true;
        } catch (Exception $e) {
            $transaction->rollBack();
            Yii::error("Error create expert: " . $e->getMessage(), __METHOD__);
            if (isset($user)) $this->userService->deleteUser($user?->id);
            return false;
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

        return $this->userService->updateUser($id, [
            'surname' => $expertModel->surname, 
            'name' => $expertModel->name, 
            'patronymic' => $expertModel->patronymic,
            'updated_at' => $expertModel->updated_at,
        ]);
    }

    /**
     * Deletes multiple experts.
     * @param array $expertIds
     * @return bool
     */
    public function deleteExperts(array $expertIds): bool
    {
        if (in_array(Yii::$app->user->id, $expertIds)) return false;

        Users::updateAll(['statuses_id' => Statuses::getStatusId(Statuses::DELETING)], ['id' => $expertIds]);
        foreach ($expertIds as $id) {
            Yii::$app->queue->push(new DeleteExpertEnvironment(['id' => $id]));
        }
        return true;
    }

    public function deleteExpertEnvironment(?int $id): bool
    {
        $user = Users::findOne(['id' => $id]);
        if (!$user) return true;

        try {
            $eventIds = array_column($user->events, 'id');
            $this->eventService->deleteEvents($eventIds);
            return true;
        } catch (Exception $e) {
            Yii::error("\nFailed to remove the expert ($id):\n{$e->getMessage()}", __METHOD__);
            return false;
        }
    }
}