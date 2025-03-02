<?php

namespace common\models;

use app\components\DbComponent;
use common\modules\flash\Module;
use Exception;
use Yii;
use yii\base\Model;
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
    const SCENARIO_CREATE_MODULES = 'create-modules';

    public int $countModules = 1;

    public function scenarios()
    {
        $scenarios = parent::scenarios();
        $scenarios[self::SCENARIO_CREATE_MODULES] = ['countModules'];
        return $scenarios;
    }

    public function beforeSave($insert)
    {
        if (parent::beforeSave($insert)) {
            if (!$this->isNewRecord) {
                return $this->changePrivilegesDbStudents();
            }

            return true;
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
            ['number', 'default', 'value' => ($this->nextNumModule())],
            [['events_id'], 'exist', 'skipOnError' => true, 'targetClass' => Events::class, 'targetAttribute' => ['events_id' => 'id']],
            ['countModules', 'number', 'min' => 1],

            ['countModules', 'required', 'on' => self::SCENARIO_CREATE_MODULES],
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
            'number' => 'Номер модуля',
            'countModules' => 'Кол-во модулей',
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

    public function nextNumModule()
    {
        $lastModule = self::find()
            ->where(['events_id' => $this->events_id])
            ->orderBy([
                'id' => SORT_DESC
            ])
            ->limit(1)
            ->one()
        ;
        
        return is_null($lastModule) ? 1 : (++$lastModule->number);
    }

    /**
     * Get ActiveDataProvider modules
     * 
     * @return ActiveDataProvider
     */
    public static function getDataProviderModules(int $records): ActiveDataProvider
    {
        $event_id = Events::getIdByExpert(Yii::$app->user->id);

        return new ActiveDataProvider([
            'query' => Modules::find()
                ->select(['id', 'status', 'number'])
                ->where(['events_id' => $event_id])
            ,
            'pagination' => [
                'pageSize' => $records,
                'route' => 'modules',
            ],
        ]);
    }

    public static function createModule()
    {
        $transaction = Yii::$app->db->beginTransaction();

        try {
            $module = new Modules();
            $module->events_id = Events::getEventByExpert(Yii::$app->user->id)?->id;
            
            if ($module->save()) {
                $students = StudentsEvents::findAll(['events_id' => $module->events_id]);
                
                foreach ($students as $student) {
                    if (!$student->createDbModule($module) || !$student->createDirectoriesModule($module)) {
                        $transaction->rollBack();
                        self::deleteModule($module?->id);
                        return false;
                    }
                }

                $transaction->commit();
                return true;
            }

            $transaction->rollBack();
            self::deleteModule($module?->id);
        } catch(\Exception $e) {
            $transaction->rollBack();
            self::deleteModule($module?->id);
        } catch(\Throwable $e) {
            $transaction->rollBack();
            self::deleteModule($module?->id);
        }

        return false;
    }

    /**
     * Changes the activity status of the module.
     * 
     * @return bool returns the value `true` if the status has been successfully changed.
     * 
     * @throws Exception|Throwable generated an exception if an error occurred when changing the status.
     */
    public function changeStatus(int $status): bool
    {
        $this->status = $status;
        return $this->save();
    }

    public function changeStatuses(array $modules)
    {
        $result = [];
        $model = new Modules();
        $transaction = Yii::$app->db->beginTransaction();

        try {
            foreach ($modules as $module) {
                if (isset($module['id']) && isset($module['status'])) {
                    $result[] = [
                        'success' => $model->changeStatus($module['id'], $module['status']),
                        'id' => $module['id'],
                        'status' => $module['status'],
                    ];
                }
            }
        } catch(\Exception $e) {
            $transaction->rollBack();
        } catch(\Throwable $e) {
            $transaction->rollBack();
        }

        return $result;
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
            var_dump($e);die;
        } catch(\Throwable $e) {
            $transaction->rollBack();
        }

        return false;
    }

    public static function deleteModules(array $modulesId): bool
    {
        foreach ($modulesId as $moduleId) {
            if (!self::deleteModule($moduleId)) {
                return false;
            }
        }

        return true;
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
            if (Yii::$app->dbComponent->deleteDb($student->getDbTitle($login, $this->number))) {
                $student->deleteDirectoryModule($login, $this->number);
            } else {
                return false;
            }
        }

        return true;
    }
}
