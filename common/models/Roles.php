<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "{{%roles}}".
 *
 * @property int $id
 * @property string $title
 * 
 * @property User[] $users
 */
class Roles extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%roles}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['id', 'title'], 'required'],
            [['id'], 'integer'],
            [['title'], 'string', 'max' => 255],
            [['id'], 'unique'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'title' => 'Роль',
        ];
    }

    /**
     * Gets query for [[Users]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getUsers()
    {
        return $this->hasMany(Users::class, ['roles_id' => 'id']);
    }

    /**
     * Get role id by  title
     * 
     * @param string $title
     * @return int|null
     */
    public static function getRoleId(string $title): int|null
    {
        return (self::findOne(['title' => $title]))?->id;
    }
}
