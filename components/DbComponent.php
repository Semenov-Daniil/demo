<?php

namespace app\components;

use Yii;
use yii\base\Component;

class DbComponent extends Component
{
    public string $host = '';

    public function init()
    {
        parent::init();

        if (empty($this->host)) {
            $this->host = $this->getHostBd();
        }
    }

    public static function createDb($title)
    {
        $transaction = Yii::$app->db->beginTransaction();
        try {
            Yii::$app->db->createCommand("CREATE DATABASE $title;")
                ->execute();
            $transaction->commit();
            return true;
        } catch(\Exception $e) {
            $transaction->rollBack();
            throw $e;
        } catch(\Throwable $e) {
            $transaction->rollBack();
            throw $e;
        }

        return false;
    }

    public static function deleteDb($title)
    {
        $transaction = Yii::$app->db->beginTransaction();
        try {
            Yii::$app->db->createCommand("DROP DATABASE $title;")
                ->execute();
            $transaction->commit();
            return true;
        } catch(\Exception $e) {
            $transaction->rollBack();
            throw $e;
        } catch(\Throwable $e) {
            $transaction->rollBack();
            throw $e;
        }

        return false;
    }

    public static function createUser($login, $password)
    {
        $transaction = Yii::$app->db->beginTransaction();
        try {
            $host = self::getHostBd();
            Yii::$app->db->createCommand("
                CREATE USER '$login'@'$host' IDENTIFIED BY '$password';
                REVOKE ALL PRIVILEGES ON information_schema.* FROM '$login'@'$host';
                REVOKE ALL PRIVILEGES ON performance_schema.* FROM '$login'@'$host';
                FLUSH PRIVILEGES;
            ")
                ->execute();
            $transaction->commit();
            return true;
        } catch(\Exception $e) {
            $transaction->rollBack();
            throw $e;
        } catch(\Throwable $e) {
            $transaction->rollBack();
            throw $e;
        }
        
        return false;
    }

    public static function deleteUser($login)
    {
        $transaction = Yii::$app->db->beginTransaction();
        try {
            $host = self::getHostBd();
            Yii::$app->db->createCommand("DROP USER '$login'@'$host';")
                ->execute();
            $transaction->commit();
            return true;
        } catch(\Exception $e) {
            $transaction->rollBack();
            throw $e;
        } catch(\Throwable $e) {
            $transaction->rollBack();
            throw $e;
        }
        
        return false;
    }

    public static function addRuleDb($login, $db)
    {
        $transaction = Yii::$app->db->beginTransaction();
        try {
            $host = self::getHostBd();
            Yii::$app->db->createCommand("GRANT SELECT, INSERT, UPDATE, DELETE, CREATE, INDEX, ALTER ON $db.* TO '$login'@'$host';FLUSH PRIVILEGES;")
                ->execute();
            $transaction->commit();
            return true;
        } catch(\Exception $e) {
            $transaction->rollBack();
            throw $e;
        } catch(\Throwable $e) {
            $transaction->rollBack();
            throw $e;
        }

        return false;
    }

    public static function getHostBd()
    {
        $dsn = Yii::$app->db->dsn;
        preg_match('/host=([^;]+)/', $dsn, $matches);
        return $matches[1];
    }
}