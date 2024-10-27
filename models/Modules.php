<?php

namespace app\models;

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
     * Get DataProvider experts
     * 
     * @return array
     */
    public static function getDataProviderModules()
    {
        return new ActiveDataProvider([
            'query' => Modules::find()
                ->select(['id', 'status', 'number'])
                ->where(['competencies_id' => Yii::$app->user->id])
                ,
        ]);
    }

    public function changeStatus()
    {
        $transaction = Yii::$app->db->beginTransaction();   
        try {
            if ($module = self::findOne(['id' => $this->id])) {
                $module->status = $this->status;
                
                if ($module->save()) {
                    if ($module->changeRulesDbStudent()) {
                        $transaction->commit();
                        $answer = [
                            'code' => 200,
                            'response' => [
                                'success' => true
                            ]
                        ];
                    } else {
                        $transaction->rollBack();
                        $answer = [
                            'code' => 500,
                            'response' => [
                                'error' => [
                                    'errors' => 'Failed to change database privileges for student.',
                                ],
                            ]
                        ];
                    }
                } else {
                    $transaction->rollBack();
                    $answer = [
                        'code' => 422,
                        'response' => [
                            'error' => [
                                'errors' => $module->errors,
                            ],
                        ]
                    ];
                }
            } else {
                $answer = [
                    'code' => 404,
                    'response' => [
                        'error' => [
                            'message' => 'Not Found',
                        ],
                    ]
                ];
            }
        } catch(\Exception $e) {
            $transaction->rollBack();
            $answer = [
                'code' => 500,
                'response' => [
                    'error' => [
                        'errors' => $e->getMessage(),
                    ],
                ]
            ];
        } catch(\Throwable $e) {
            $transaction->rollBack();
            $answer = [
                'code' => 500,
                'response' => [
                    'error' => [
                        'errors' => $e->getMessage(),
                    ],
                ]
            ];
        }
        return $answer;
    }

    public function changeRulesDbStudent()
    {
        $students = StudentsCompetencies::findAll(['competencies_id' => $this->competencies_id]);

        foreach ($students as $student) {
            $change = ($this->status ? $student->addRulesDbStudent($this->number) : $student->deleteRulesDbStudent($this->number));
            if (!$change) {
                return false;
            }
        }

        return true;
    }

    public function deleteModule($id)
    {
        $transaction = Yii::$app->db->beginTransaction();   
        try {
            if ($module = self::findOne(['id' => $id])) {
                $module->delete();
                // $module->deleteModuleStudent();
                $transaction->rollBack();
                $answer = [
                    'code' => 500,
                    'response' => [
                        'error' => [
                            'errors' => 'The Student DB could not be deleted.',
                        ],
                    ]
                ];
                // if ($module->deleteModuleStudent()) {
                //     $transaction->commit();
                //         $answer = [
                //             'code' => 200,
                //             'response' => [
                //                 'success' => true
                //             ]
                //         ];
                // } else {
                //     $transaction->rollBack();
                //     $answer = [
                //         'code' => 500,
                //         'response' => [
                //             'error' => [
                //                 'errors' => 'The Student DB could not be deleted.',
                //             ],
                //         ]
                //     ];
                // }
            } else {
                $answer = [
                    'code' => 404,
                    'response' => [
                        'error' => [
                            'message' => 'Not Found',
                        ],
                    ]
                ];
            }
        } catch(\Exception $e) {
            $transaction->rollBack();
            $answer = [
                'code' => 500,
                'response' => [
                    'error' => [
                        'errors' => $e->getMessage(),
                    ],
                ]
            ];
        } catch(\Throwable $e) {
            $transaction->rollBack();
            $answer = [
                'code' => 500,
                'response' => [
                    'error' => [
                        'errors' => $e->getMessage(),
                    ],
                ]
            ];
        }
        return $answer;
    }

    public function deleteModuleStudent()
    {
        $students = StudentsCompetencies::findAll(['competencies_id' => $this->competencies_id]);

        foreach ($students as $student) {
            if (!$student->deleteDbStudent($this->number) || !$student->deleteDirStudent($this->number)) {
                return false;
            }
        }

        return true;
    }
}
