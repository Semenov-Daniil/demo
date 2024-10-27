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

    /**
     * Creates a new database
     * 
     * @param string $title Name of the database to be created
     * @return bool Returns `true` if the database was created successfully.
     * @throws Exception|Throwable Throws an exception if an error occurred while creating the database.
     */
    public static function createDb(string $title): bool
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

    /**
     * Deletes the database
     * 
     * @param string $title Name of the database to be deleted
     * @return bool Returns `true` if the database has been successfully deleted.
     * @throws Exception|Throwable Throws an exception if an error occurred while deleting the database.
     */
    public static function deleteDb(string $title): bool
    {
        try {
            Yii::$app->db->createCommand("DROP DATABASE :title;", [
                ':title' => $title,
            ])
                ->execute();
            return true;
        } catch(\Exception $e) {
            throw $e;
        } catch(\Throwable $e) {
            throw $e;
        }

        return false;
    }

    /**
     * Creates a new MySQL user.
     * 
     * @param string $login New user login.
     * @param string $password New user password.
     * 
     * @return bool Returns `true` if the user was successfully created.
     * 
     * @throws Exception|Throwable Throws an exception if an error occurred while creating a user.
     */
    public static function createUser(string $login, string $password): bool
    {
        try {
            Yii::$app->db->createCommand("
                CREATE USER ':login'@':host' IDENTIFIED BY ':password';
                REVOKE ALL PRIVILEGES ON information_schema.* FROM ':login'@':host';
                REVOKE ALL PRIVILEGES ON performance_schema.* FROM ':login'@':host';
                FLUSH PRIVILEGES;
            ", [
                ':login' => $login,
                ':host' => self::getHost(),
                ':password' => $password,
            ])
                ->execute();
            return true;
        } catch(\Exception $e) {
            throw $e;
        } catch(\Throwable $e) {
            throw $e;
        }
        
        return false;
    }

    /**
     * Deletes the MySQL user.
     * 
     * @param string $login User login.
     * 
     * @return bool Returns `true` if the user was successfully deleted.
     * 
     * @throws Exception|Throwable Throws an exception if an error occurred while deleting a user.
     */
    public static function deleteUser(string $login): bool
    {
        try {
            Yii::$app->db->createCommand("DROP USER ':login'@':host';", [
                ':login' => $login,
                ':host' => self::getHost(),
            ])
                ->execute();
            return true;
        } catch(\Exception $e) {
            throw $e;
        } catch(\Throwable $e) {
            throw $e;
        }
        
        return false;
    }

    /**
     * Grants the user privileges to the database
     * 
     * @param string $login User login.
     * @param string $db Database name.
     * 
     * @return bool Returns `true` if the user was successfully execution.
     * 
     * @throws Exception|Throwable Throws an exception if an error occurs while granting privileges.
     */
    public static function grantPrivileges(string $login, string $db): bool
    {
        try {
            Yii::$app->db->createCommand("
                GRANT SELECT, INSERT, UPDATE, DELETE, CREATE, INDEX, ALTER ON :db.* TO ':login'@':host';
                FLUSH PRIVILEGES;
            ", [
                ':db' => $db,
                ':login' => $login,
                ':host' => self::getHost(),
            ])
                ->execute();
            return true;
        } catch(\Exception $e) {
            throw $e;
        } catch(\Throwable $e) {
            throw $e;
        }

        return false;
    }

    /**
     * Grants the user privileges to the database
     * 
     * @param string $login User login.
     * @param string $db Database name.
     * 
     * @return bool Returns `true` if the user was successfully execution.
     * 
     * @throws Exception|Throwable Throws an exception if an error occurs while granting privileges.
     */
    public static function deletePrivileges(string $login, string $db): bool
    {
        try {
            Yii::$app->db->createCommand("
                REVOKE ALL PRIVILEGES ON :db.* FROM ':login'@':host';
                FLUSH PRIVILEGES;
            ", [
                ':db' => $db,
                ':login' => $login,
                ':host' => self::getHost(),
            ])
                ->execute();
            return true;
        } catch(\Exception $e) {
            throw $e;
        } catch(\Throwable $e) {
            throw $e;
        }

        return false;
    }

    public static function getHost()
    {
        $dsn = Yii::$app->db->dsn;
        preg_match('/host=([^;]+)/', $dsn, $matches);
        return $matches[1];
    }
}