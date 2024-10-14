<?php

namespace app\components;

use Yii;
use yii\base\Component;

class DbComponent extends Component
{
    public function createDb($title)
    {
        $transaction = Yii::$app->db->beginTransaction();
        try {
            Yii::$app->db->createCommand("CREATE DATABASE :title;")
                ->bindValue(':title', $title)
                ->execute();
            $transaction->commit();
            return true;
        } catch(\Exception $e) {
            $transaction->rollBack();
        } catch(\Throwable $e) {
            $transaction->rollBack();
        }
    }

    public function createUser($login, $password, $host = '%')
    {
        $transaction = Yii::$app->db->beginTransaction();
        try {
            Yii::$app->db->createCommand("CREATE USER ':login'@':host' IDENTIFIED BY ':password';FLUSH PRIVILEGES;")
                ->bindValues([
                    ':login' => $login,
                    ':password' => $password,
                    ':host' => $host,
                ])
                ->execute();
            $transaction->commit();
            return true;
        } catch(\Exception $e) {
            $transaction->rollBack();
        } catch(\Throwable $e) {
            $transaction->rollBack();
        }
    }

    public function addRuleDb($login, $db, $host = '%')
    {
        $transaction = Yii::$app->db->beginTransaction();
        try {
            Yii::$app->db->createCommand("GRANT SELECT, INSERT, UPDATE, DELETE, FILE, INDEX, ALTER ON :db.* TO ':login'@':host';FLUSH PRIVILEGES;")
                ->bindValues([
                    ':login' => $login,
                    ':db' => $db,
                    ':host' => $host,
                ])
                ->execute();
            $transaction->commit();
            return true;
        } catch(\Exception $e) {
            $transaction->rollBack();
        } catch(\Throwable $e) {
            $transaction->rollBack();
        }
    }
}