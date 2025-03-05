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
    use RandomStringTrait;

    public int|null $expert = null;
    public string $title = '';
    public int $countModules = 1;

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['expert', 'title', 'countModules'], 'required'],
            [['countModules'], 'integer', 'min' => 1],
            [['expert'], 'integer'],
            [['title'], 'string', 'max' => 255],
            ['countModules', 'default', 'value' => 1],
            [['title'], 'trim'],
            [['expert'], 'exist', 'targetClass' => Users::class, 'targetAttribute' => ['expert' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'expert' => 'Эксперт',
            'title' => 'Название чемпионата',
            'countModules' => 'Кол-во модулей',
        ];
    }

    public function createEvent()
    {
        $this->validate();

        if (!$this->hasErrors()) {
            $transaction = Yii::$app->db->beginTransaction();

            try {
                $event = new Events();
                $event->attributes = $this->attributes;
                $event->experts_id = $this->expert;

                if ($event->save()) {
                    $transaction->commit();
                    return true;
                }

                $transaction->rollBack();
            } catch(\Exception $e) {
                $transaction->rollBack();
                VarDumper::dump($e, 10, true);die;
            } catch(\Throwable $e) {
                $transaction->rollBack();
            }
        }

        return false;
    }
}
