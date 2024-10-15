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
            [['surname', 'name', 'middle_name', 'title'], 'trim'],

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
        // VarDumper::dump(StudentsCompetencies::find()
        //     ->select([
        //         'id',
        //         'surname',
        //         'name',
        //         'middle_name',
        //         'login',
        //         Passwords::tableName() . '.password',
        //     ])
        //     ->where(['roles_id' => Roles::getRoleId(self::TITLE_ROLE_STUDENT), 'competencies_id' => Yii::$app->user->id])
        //     ->joinWith('passwords', false)
        //     ->joinWith('users', false)
        //     ->asArray(),
        //  10, true);die;
        
        return new ActiveDataProvider([
            'query' => StudentsCompetencies::find()
                ->select([
                    'students_id',
                    'surname',
                    'name',
                    'middle_name',
                    'login',
                    Passwords::tableName() . '.password',
                ])
                ->where(['roles_id' => Roles::getRoleId(self::TITLE_ROLE_STUDENT), 'competencies_id' => Yii::$app->user->id])
                ->joinWith('passwords', false)
                ->joinWith('users', false)
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
                    $competence->users_id = $user->id;
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
                if ($user->addStudent()) {
                    $student_competenc = new StudentsCompetencies();
                    $student_competenc->students_id = $user->id;
                    $student_competenc->competencies_id = Yii::$app->user->id;
                    if ($student_competenc->save()) {
                        $transaction->commit();
                        return true;
                    }
                }
            } catch(\Exception $e) {
                $transaction->rollBack();
                var_dump($e);die;
            } catch(\Throwable $e) {
                $transaction->rollBack();
                var_dump($e);die;
            }
        }

        return false;
    }
}