<?php

namespace app\models;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use yii\helpers\VarDumper;

/**
 * ExpertsCompetencies is the model underlying the communication between Experts and Competencies.
 */
class ExpertsCompetencies extends Model 
{
    public string $surname = '';
    public string $name = '';
    public string $middle_name = '';
    public string $title = '';
    public int $num_modules = 1;

    const TITLE_ROLE_EXPERT = "expert";

    /**
     * {@inheritdoc}
     */
    public function rules(): array
    {
        return [
            [['surname', 'name', 'title', 'num_modules'], 'required'],
            [['surname', 'name', 'middle_name', 'title'], 'string', 'max' => 255],
            [['num_modules'], 'integer', 'min' => 1],
            [['surname', 'name', 'middle_name', 'title'], 'trim'],
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
            'middle_name' => 'Отчество',
            'title' => 'Название компетенции',
            'num_modules' => 'Кол-во модулей',
        ];
    }

    /**
     * Get DataProvider experts
     * 
     * @return array
     */
    public static function getDataProviderExperts($page)
    {
        return new ActiveDataProvider([
            'query' => Users::find()
                ->select([
                    'id',
                    'surname',
                    'name',
                    'middle_name',
                    'login',
                    Passwords::tableName() . '.password',
                    Competencies::tableName() . '.title',
                    Competencies::tableName() . '.num_modules',
                ])
                ->where(['roles_id' => Roles::getRoleId(self::TITLE_ROLE_EXPERT)])
                ->joinWith('passwords', false)
                ->joinWith('competencies', false)
                ->asArray(),
            'pagination' => [
                'pageSize' => $page,
            ],
        ]);
    }

    /**
     * Add expert
     * 
     * @return bool
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
                    $competence = new Competencies();
                    $competence->attributes = $this->attributes;
                    $competence->experts_id = $user->id;
                    if ($competence->save()) {
                        $transaction->commit();
                        return true;
                    }
                }
            } catch(\Exception $e) {
                $transaction->rollBack();
            } catch(\Throwable $e) {
                $transaction->rollBack();
            }
        }

        return false;
    }
}