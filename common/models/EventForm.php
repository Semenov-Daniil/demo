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
    public string $title = '';
    public int $countModules = 1;

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['title', 'countModules'], 'required'],
            [['countModules'], 'integer', 'min' => 1],
            [['expert'], 'integer'],
            [['title'], 'string', 'max' => 255],
            ['countModules', 'default', 'value' => 1],
            [['title'], 'trim'],
            [['expert'], 'exist', 'targetClass' => Users::class, 'targetAttribute' => ['expert' => 'id']],
            ['expert', 'required', 'when' => function($model) {
                return Yii::$app->user->can('sExpert');
            }, 'message' => 'Необходимо выбрать эксперта.'],
            ['expert', 'expertValidate'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'expert' => 'Эксперт',
            'title' => 'Название события',
            'countModules' => 'Кол-во модулей',
        ];
    }

    public function expertValidate($attribute, $params)
    {
        if (!$this->hasErrors()) {
            $user = Users::findOne($this->expert);

            if (!$user || !($user->statuses_id == Statuses::getStatusId(Statuses::READY) || $user->statuses_id == Statuses::getStatusId(Statuses::CONFIGURING))) {
                $this->addError($attribute, 'Обновите выбор эксперта');
            }
        }
    }
}
