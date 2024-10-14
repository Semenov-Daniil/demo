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

    const SCENARIO_ADD_EXPERT = "add-expert";
    const TITLE_ROLE_EXPERT = "expert";
    const TITLE_ROLE_STUDENT = "student";

    /**
     * {@inheritdoc}
     */
    public function rules(): array
    {
        return [
            [['surname', 'name'], 'required'],
            [['surname', 'name', 'middle_name', 'title'], 'string', 'max' => 255],
            [['num_modules'], 'integer', 'min' => 1],

            [['title', 'num_modules'], 'required', 'on' => self::SCENARIO_ADD_EXPERT],
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
     * Get DataProvider students
     * 
     * @return array
     */
    public static function getDataProviderStudents($page)
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
                ])
                ->where(['roles_id' => Roles::getRoleId(self::TITLE_ROLE_STUDENT)])
                ->joinWith('passwords', false)
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

    /**
     * Add student
     * 
     * @return bool
     */
    public function addStudent(): bool
    {
        $this->validate();
        
        if (!$this->hasErrors()) {
            $transaction = Yii::$app->db->beginTransaction();   
            try {
                $user = new Users();
                $user->attributes = $this->attributes;
                $user->addStudent();

                $student_competenc = new StudentsCompetencies();
                $student_competenc->students_id = $user->id;
                $student_competenc->competencies_id = Yii::$app->user->id;
                $student_competenc->save();

                $transaction->commit();
    
                Yii::$app->session->setFlash('success', "Студент успешно добавлен.");
                return true;
            } catch(\Exception $e) {
                $transaction->rollBack();
                Yii::$app->session->setFlash('error', "Не удалось добавить студента.");
            } catch(\Throwable $e) {
                $transaction->rollBack();
                Yii::$app->session->setFlash('error', "Не удалось добавить студента.");
            }
        }

        return false;
    }
}