<?php

namespace app\components;

use Yii;
use yii\base\Component;
use yii\db\Transaction;

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
        try {
            Yii::$app->db->createCommand("CREATE DATABASE $title;")
                ->execute();
            return true;
        } catch(\Exception $e) {
            throw $e;
        } catch(\Throwable $e) {
            throw $e;
        }

        return false;
    }

    public static function deleteDb($title)
    {
        try {
            Yii::$app->db->createCommand("DROP DATABASE $title;")
                ->execute();
            return true;
        } catch(\Exception $e) {
            throw $e;
        } catch(\Throwable $e) {
            throw $e;
        }

        return false;
    }

    public static function createUser($login, $password)
    {
        try {
            $host = self::getHostBd();
            Yii::$app->db->createCommand("
                CREATE USER '$login'@'$host' IDENTIFIED BY '$password';
                FLUSH PRIVILEGES;
                REVOKE ALL PRIVILEGES ON information_schema.* FROM '$login'@'$host';
                REVOKE ALL PRIVILEGES ON performance_schema.* FROM '$login'@'$host';
                FLUSH PRIVILEGES;
            ")
                ->execute();
            return true;
        } catch(\Exception $e) {
            throw $e;
        } catch(\Throwable $e) {
            throw $e;
        }
        
        return false;
    }

    public static function deleteUser($login)
    {
        try {
            $host = self::getHostBd();
            Yii::$app->db->createCommand("DROP USER '$login'@'$host';")
                ->execute();
            return true;
        } catch(\Exception $e) {
            throw $e;
        } catch(\Throwable $e) {
            throw $e;
        }
        
        return false;
    }

    public static function addRuleDb($login, $db)
    {
        try {
            $host = self::getHostBd();
            Yii::$app->db->createCommand("GRANT SELECT, INSERT, UPDATE, DELETE, CREATE, INDEX, ALTER ON $db.* TO '$login'@'$host';FLUSH PRIVILEGES;")
                ->execute();
            return true;
        } catch(\Exception $e) {
            throw $e;
        } catch(\Throwable $e) {
            throw $e;
        }

        return false;
    }

    public static function deleteRuleDb($login, $db)
    {
        try {
            $host = self::getHostBd();
            Yii::$app->db->createCommand("REVOKE ALL PRIVILEGES ON $db.* FROM '$login'@'$host';FLUSH PRIVILEGES;")
                ->execute();
            return true;
        } catch(\Exception $e) {
            throw $e;
        } catch(\Throwable $e) {
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