<?php

namespace app\models;

use app\components\DbComponent;
use Yii;
use yii\data\ActiveDataProvider;
use yii\db\Transaction;
use yii\helpers\VarDumper;

/**
 * This is the model class for table "dm_modules".
 *
 * @property int $id
 * @property int $competencies_id
 * @property int $status
 * @property int $number
 *
 * @property Competencies $competencies
 */
class Modules extends \yii\db\ActiveRecord
{
    public function beforeSave($insert)
    {
        if (parent::beforeSave($insert)) {
            return $this->changePrivilegesDbStudents();
        } else {
            return false;
        }
    }

    public function beforeDelete()
    {
        if (!parent::beforeDelete()) {
            return false;
        }

        return $this->deleteModulesStudents();
    }

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'dm_modules';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['competencies_id'], 'required'],
            [['id', 'competencies_id', 'status', 'number'], 'integer'],
            ['status', 'default', 'value' => 1],
            ['number', 'default', 'value' => (self::find()->where(['competencies_id' => $this->competencies_id])->count() + 1)],
            [['competencies_id'], 'exist', 'skipOnError' => true, 'targetClass' => Competencies::class, 'targetAttribute' => ['competencies_id' => 'experts_id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'competencies_id' => 'Компетенция',
            'status' => 'Статус',
        ];
    }

    /**
     * Gets query for [[Competencies]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getCompetencies()
    {
        return $this->hasOne(Competencies::class, ['experts_id' => 'competencies_id']);
    }

    /**
     * Get ActiveDataProvider modules
     * 
     * @return ActiveDataProvider
     */
    public static function getDataProviderModules(): ActiveDataProvider
    {
        return new ActiveDataProvider([
            'query' => Modules::find()
                ->select(['id', 'status', 'number'])
                ->where(['competencies_id' => Yii::$app->user->id])
                ,
        ]);
    }

    /**
     * Changes the activity status of the module.
     * 
     * @return bool returns the value `true` if the status has been successfully changed.
     * 
     * @throws Exception|Throwable generated an exception if an error occurred when changing the status.
     */
    public function changeStatus(): bool
    {
        $transaction = Yii::$app->db->beginTransaction();   
        try {
            if ($module = self::findOne(['id' => $this->id])) {
                $module->status = $this->status;
                
                if ($module->save()) {
                    $transaction->commit();
                    return true;
                }

                $transaction->rollBack();
            }
            return false;
        } catch(\Exception $e) {
            $transaction->rollBack();
        } catch(\Throwable $e) {
            $transaction->rollBack();
        }

        return false;
    }

    /**
     * Changes the privileges of the student databases depending on the activity status of the module.
     * 
     * @return bool returns the value `true` if the privileges has been successfully changed.
     * 
     */
    public function changePrivilegesDbStudents(): bool
    {
        $students = StudentsCompetencies::findAll(['competencies_id' => $this->competencies_id]);

        foreach ($students as $student) {
            $login  = $student->users->login; 
            if (!($this->status ? $student->grantPrivilegesDbStudent($login, $this->number) : $student->revokePrivilegesDbStudent($login, $this->number))) {
                return false;
            }
        }

        return true;
    }

    /**
     * Deletes the module.
     * 
     * @param string $id module ID.
     * 
     * @return bool return `true` if the module was successfully deleted.
     */
    public static function deleteModule(string $id): bool
    {
        $transaction = Yii::$app->db->beginTransaction();   
        try {
            if ($module = self::findOne(['id' => $id])) {
                if ($module->delete()) {
                    $transaction->commit();
                    return true;
                }

                $transaction->rollBack();
            }
            return false;
        } catch(\Exception $e) {
            $transaction->rollBack();
        } catch(\Throwable $e) {
            $transaction->rollBack();
        }

        return false;
    }

    /**
     * Deletes a module from students.
     * 
     * @return bool return `true` if the module was successfully deleted.
     */
    public function deleteModulesStudents(): bool
    {
        $students = StudentsCompetencies::findAll(['competencies_id' => $this->competencies_id]);

        foreach ($students as $student) {
            $login  = $student->users->login; 
            if (DbComponent::deleteDb($student->getDbTitle($login, $this->number))) {
                $student->deleteDirectoryModule($login, $this->number);
            } else {
                return false;
            }
        }

        return true;
    }
}
