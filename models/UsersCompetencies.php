<?php

namespace app\models;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use yii\helpers\VarDumper;

/**
 * UsersCompetencies is the model underlying the communication between Users and Competencies.
 */
class UsersCompetencies extends Model 
{
    public string $surname = '';
    public string $name = '';
    public string $middle_name = '';
    public string $title = '';
    public int $num_modules = 1;

    /**
     * {@inheritdoc}
     */
    public function rules(): array
    {
        return [
            [['surname', 'name', 'title', 'num_modules'], 'required'],
            [['surname', 'name', 'middle_name', 'title'], 'string', 'max' => 255],
            [['num_modules'], 'integer', 'min' => 1],
            [['title'], 'string', 'max' => 255],
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
     * Add expert
     * 
     * @return array
     */
    public static function getDataProviderExpert($page)
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
                ->where(['roles_id' => Roles::getRoleId('expert')])
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
                $user = new Users(['scenario' => Users::SCENARIO_ADD_EXPERT]);
                $user->attributes = $this->attributes;
                $user->addExpert();
                
                $competence = new Competencies();
                $competence->attributes = $this->attributes;
                $competence->users_id = $user->id;
                $competence->save();
    
                $transaction->commit();
    
                Yii::$app->session->setFlash('success', "Эксперта успешно добавлен.");
                return true;
            } catch(\Exception $e) {
                $transaction->rollBack();
                Yii::$app->session->setFlash('error', "Не удалось добавить эксперта.");
            } catch(\Throwable $e) {
                $transaction->rollBack();
                Yii::$app->session->setFlash('error', "Не удалось добавить эксперта.");
            }
        }

        return false;
    }
}