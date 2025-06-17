<?php

namespace common\models;

use app\components\AppComponent;
use app\components\DbComponent;
use app\components\FileComponent;
use common\traits\RandomStringTrait;
use Yii;
use yii\base\Model;
use yii\db\ActiveRecord;
use yii\helpers\VarDumper;

class EventForm extends Model
{
    public int|null $expert = null;
    public int|null $expertUpdate = null;
    public string $title = '';
    public int $countModules = 1;
    public string $updated_at = '';

    const SCENARIO_UPDATE = 'update';

    public function scenarios()
    {
        $scenarios = parent::scenarios();
        $scenarios[self::SCENARIO_DEFAULT] = ['title', 'expert', 'countModules'];
        $scenarios[self::SCENARIO_UPDATE] = ['title', 'expertUpdate', 'updated_at'];
        return $scenarios;
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['title', 'countModules'], 'required'],
            [['countModules'], 'integer', 'min' => 1],
            [['expert', 'expertUpdate'], 'integer'],
            ['updated_at', 'safe'],
            [['title'], 'string', 'max' => 255],
            ['countModules', 'default', 'value' => 1],
            [['title'], 'trim'],
            [['expert'], 'exist', 'targetClass' => Users::class, 'targetAttribute' => ['expert' => 'id']],
            [['expertUpdate'], 'exist', 'targetClass' => Users::class, 'targetAttribute' => ['expertUpdate' => 'id']],
            [['expert', 'expertUpdate'], 'required', 'when' => function($model) {
                return Yii::$app->user->can('sExpert');
            }, 'message' => 'Необходимо выбрать эксперта.'],
            [['expert', 'expertUpdate'], 'expertValidate'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'expert' => 'Эксперт',
            'expertUpdate' => 'Эксперт',
            'title' => 'Название события',
            'countModules' => 'Кол-во модулей',
        ];
    }

    public function expertValidate($attribute, $params)
    {
        if (!$this->hasErrors()) {
            $expert_id = $this->expert ?: $this->expertUpdate;
            $user = Users::findOne($expert_id);

            if (!$user || !($user->statuses_id == Statuses::getStatusId(Statuses::READY) || $user->statuses_id == Statuses::getStatusId(Statuses::CONFIGURING))) {
                $this->addError($attribute, 'Обновите выбор эксперта');
            }
        }
    }
}
