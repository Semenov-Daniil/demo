<?php

namespace common\models;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use yii\db\Expression;
use yii\helpers\VarDumper;

class Experts extends Model 
{
    const TITLE_ROLE_EXPERT = 'expert';
    
    public static function getExperts()
    {
        return Users::find()
            ->select(['CONCAT(surname, \' \', name, COALESCE(CONCAT(\' \', patronymic), \'\')) AS fullName'])
            ->where(['roles_id' => Roles::getRoleId(self::TITLE_ROLE_EXPERT)])
            ->orderBy([
                'fullName' => SORT_ASC,
            ])
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
    public static function getExpertsDataProvider(int $records = 10): ActiveDataProvider
    {
        $query = Users::find()
            ->select([
                Users::tableName() . '.id',
                'CONCAT(surname, " ", name, COALESCE(CONCAT(" ", patronymic), "")) AS fullName',
                'login',
                'encrypted_password AS password'
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
}