<?php

namespace common\models;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use yii\db\Expression;
use yii\helpers\VarDumper;

class Experts extends Model 
{
    public string $surname = '';
    public string $name = '';
    public string $patronymic = '';

    const TITLE_ROLE_EXPERT = "expert";

    /**
     * {@inheritdoc}
     */
    public function rules(): array
    {
        return [
            [['surname', 'name'], 'required'],
            [['surname', 'name', 'patronymic'], 'string', 'max' => 255],
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
        ];
    }

    public static function getExperts()
    {
        return Users::find()
            ->select(['CONCAT(surname, \' \', name, COALESCE(CONCAT(\' \', patronymic), \'\')) AS fullName'])
            ->where(['roles_id' => Roles::getRoleId(Users::TITLE_ROLE_EXPERT)])
            ->indexBy('id')
            ->column()
        ;
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
                EncryptedPasswords::tableName() . '.encrypted_password AS password'
            ])
            ->where(['roles_id' => Roles::getRoleId(self::TITLE_ROLE_EXPERT)])
            ->joinWith('encryptedPassword', false)
            ->orderBy([
                new Expression('CASE WHEN ' . Users::tableName() . '.id = ' . Yii::$app->user->id . ' THEN 0 ELSE 1 END')
            ])
            ->asArray()
        ;

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'pageSize' => $records,
                'route' => 'experts',
            ],
        ]);

        $models = $dataProvider->getModels();
        foreach ($models as &$model) {
            $model['password'] = EncryptedPasswords::decryptByPassword($model['password']);
        }
        unset($model);
        $dataProvider->setModels($models);

        return $dataProvider;
    }

    public static function findExpert(?int $id = null): static|null
    {
        $model = null;

        if ($user = Users::findOne(['id' => $id])) {
            $model = new self();
            $model->attributes = $user->attributes;
        }

        return $model;
    }

    /**
     * Adds a new user with the `expert` role
     * 
     * @return bool returns the value `true` if the expert has been successfully added.
     * 
     * @throws Exception|Throwable throws an exception if an error occurs when adding a expert.
     */
    public function createExpert(): bool
    {
        $this->validate();

        if (!$this->hasErrors()) {
            $transaction = Yii::$app->db->beginTransaction();

            try {
                $user = new Users();
                $user->attributes = $this->attributes;

                if ($user->addExpert()) {
                    $transaction->commit();
                    return true;
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

    public function updateExpert(?int $id = null): bool
    {
        $this->validate();

        if (!$this->hasErrors()) {
            $user = Users::findOne(['id' => $id]);

            if (!empty($user)) {
                $user->attributes = $this->attributes;
                return $user->save();
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