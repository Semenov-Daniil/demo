<?php

namespace app\models;

use app\components\DbComponent;
use Yii;
use yii\data\ActiveDataProvider;
use yii\helpers\VarDumper;

/**
 * This is the model class for table "dm_modules".
 *
 * @property int $id
 * @property int $events_id
 * @property int $status
 * @property int $number
 *
 * @property Events $event
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
        return '{{%modules}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['events_id'], 'required'],
            [['id', 'events_id', 'status', 'number'], 'integer'],
            ['status', 'default', 'value' => 1],
            ['number', 'default', 'value' => (self::find()->where(['events_id' => $this->events_id])->count() + 1)],
            [['events_id'], 'exist', 'skipOnError' => true, 'targetClass' => Events::class, 'targetAttribute' => ['events_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'events_id' => 'Компетенция',
            'status' => 'Статус',
        ];
    }

    /**
     * Gets query for [[Events]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getEvent()
    {
        return $this->hasOne(Events::class, ['id' => 'events_id']);
    }

    /**
     * Get ActiveDataProvider modules
     * 
     * @return ActiveDataProvider
     */
    public static function getDataProviderModules(): ActiveDataProvider
    {
        $event_id = Events::getIdByExpert(Yii::$app->user->id);

        return new ActiveDataProvider([
            'query' => Modules::find()
                ->select(['id', 'status', 'number'])
                ->where(['events_id' => $event_id])
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
        $students = StudentsEvents::findAll(['events_id' => $this->events_id]);

        foreach ($students as $student) {
            $login  = $student->user->login; 
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
        $students = StudentsEvents::find()
            ->where(['events_id' => $this->events_id])
            ->joinWith('user')
            ->all()
        ;
        
        foreach ($students as $student) {
            $login = $student->user->login; 
            if (DbComponent::deleteDb($student->getDbTitle($login, $this->number))) {
                $student->deleteDirectoryModule($login, $this->number);
            } else {
                return false;
            }
        }

        return true;
    }
}
