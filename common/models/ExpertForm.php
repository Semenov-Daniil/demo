<?php

namespace common\models;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use yii\db\Expression;
use yii\helpers\VarDumper;

class ExpertForm extends Model 
{
    public string $surname = '';
    public string $name = '';
    public ?string $patronymic = '';
    public string $updated_at = '';

    const TITLE_ROLE_EXPERT = "expert";

    /**
     * {@inheritdoc}
     */
    public function rules(): array
    {
        return [
            [['surname', 'name'], 'required'],
            [['surname', 'name', 'patronymic'], 'string', 'max' => 255],
            [['surname', 'name', 'patronymic'], 'trim'],
            ['patronymic', 'default', 'value' => null],
            ['updated_at', 'safe'],
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
            'patronymic' => 'Отчество',
            'updated_at' => 'Последнее обновление',
        ];
    }
}