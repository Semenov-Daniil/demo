<?php

namespace common\models;

use Yii;
use yii\base\Model;

class StudentForm extends Model
{
    public string $surname = '';
    public string $name = '';
    public ?string $patronymic = '';
    public ?int $events_id = null;
    public string $updated_at = '';

    const SCENARIO_CREATE = 'create';
    const SCENARIO_UPDATE = 'update';

    public function scenarios()
    {
        $scenarios = parent::scenarios();
        $scenarios[self::SCENARIO_CREATE] = ['surname', 'name', 'patronymic', 'events_id'];
        $scenarios[self::SCENARIO_UPDATE] = ['surname', 'name', 'patronymic'];
        return $scenarios;
    }

    public function rules(): array
    {
        return [
            [['surname', 'name'], 'required'],
            [['events_id'], 'required', 'on' => self::SCENARIO_CREATE, 'message' => 'Необходимо выбрать событие.'],
            [['surname', 'name', 'patronymic'], 'string', 'max' => 255],
            ['updated_at', 'safe'],
            [['surname', 'name', 'patronymic'], 'trim'],
            ['patronymic', 'default', 'value' => null],
            [['events_id'], 'exist', 'targetClass' => Events::class, 'targetAttribute' => ['events_id' => 'id']],
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'surname' => 'Фамилия',
            'name' => 'Имя',
            'patronymic' => 'Отчество',
            'events_id' => 'Событие',
        ];
    }
}