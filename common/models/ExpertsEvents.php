<?php

namespace common\models;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use yii\helpers\VarDumper;

/**
 * ExpertsEvents is the model underlying the communication between Experts and Events.
 */
class ExpertsEvents extends Model 
{
    public string $surname = '';
    public string $name = '';
    public string $patronymic = '';
    public string $title = '';
    public int $countModules = 1;

    const TITLE_ROLE_EXPERT = "expert";

    /**
     * {@inheritdoc}
     */
    public function rules(): array
    {
        return [
            [['surname', 'name', 'title', 'countModules'], 'required'],
            [['surname', 'name', 'patronymic', 'title'], 'string', 'max' => 255],
            [['countModules'], 'integer', 'min' => 1],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels(): array
    {
        return [
            'surname' => 'Фамилия',
            'name' => 'Имя',
            'patronymic' => 'Отчество',
            'title' => 'Название события',
            'countModules' => 'Кол-во модулей',
        ];
    }

    /**
     * Get DataProvider experts
     * 
     * @param int $records number of records.
     * 
     * @return ActiveDataProvider
     */
    public static function getDataProviderExperts(int $records): ActiveDataProvider
    {
        $subQuery = Modules::find()
            ->select('COUNT(*)')
            ->where(Modules::tableName() . '.events_id = ' . Events::tableName() . '.id');

        $query = Users::find()
            ->select([
                Users::tableName() . '.id',
                'CONCAT(surname, \' \', name, COALESCE(CONCAT(\' \', patronymic), \'\')) AS fullName',
                'login',
                EncryptedPasswords::tableName() . '.encrypted_password AS encryptedPassword',
                'title as event',
                'countModules' => $subQuery
            ])
            ->where(['roles_id' => Roles::getRoleId(self::TITLE_ROLE_EXPERT)])
            ->joinWith('encryptedPassword', false)
            ->joinWith('event', false)
            ->asArray()
        ;

        return new ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'pageSize' => $records,
            ],
        ]);
    }

    /**
     * Adds a new user with the `expert` role
     * 
     * @return bool returns the value `true` if the expert has been successfully added.
     * 
     * @throws Exception|Throwable throws an exception if an error occurs when adding a expert.
     */
    public function addExpert(): bool
    {
        $this->validate();

        if (!$this->hasErrors()) {
            $transaction = Yii::$app->db->beginTransaction();

            try {
                $user = new Users();
                $user->attributes = $this->attributes;

                if ($user->addExpert()) {
                    $event = new Events();
                    $event->attributes = $this->attributes;
                    $event->experts_id = $user->id;
                    
                    if ($event->save()) {
                        $transaction->commit();
                        return true;
                    }
                }

                $transaction->rollBack();
            } catch(\Exception $e) {
                $transaction->rollBack();
            } catch(\Throwable $e) {
                $transaction->rollBack();
            }
        }

        return false;
    }

    /**
     * Deletes the expert.
     * 
     * @return bool Returns the value `true` if the expert was successfully deleted.
     */
    public static function deleteExpert(string|int $id): bool
    {
        return Users::deleteUser($id);
    }

    public static function deleteExperts(array $experts): bool
    {
        foreach ($experts as $expertId) {
            if (!self::deleteExpert($expertId)) {
                return false;
            }
        }

        return true;
    }
}