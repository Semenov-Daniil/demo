<?php

namespace common\components;

use Exception;
use Yii;
use yii\base\Component;
use yii\db\Query;

class DbComponent extends Component
{
    public string $host = '';

    public function init()
    {
        parent::init();

        if (empty($this->host)) {
            $this->host = $this->getHost();
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
            $title = Yii::$app->db->quoteTableName($title);
            Yii::$app->db->createCommand("CREATE DATABASE IF NOT EXISTS $title;")
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
            $title = Yii::$app->db->quoteTableName($title);
            Yii::$app->db->createCommand("DROP DATABASE IF EXISTS $title;")
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
     * @param string $login new user login.
     * @param string $password new user password.
     * 
     * @return bool returns `true` if the user was successfully created.
     * 
     * @throws Exception|Throwable throws an exception if an error occurred while creating a user.
     */
    public static function createUser(string $login, string $password): bool
    {
        try {
            if (!self::hasUserByLogin($login)) {
                Yii::$app->db->createCommand("
                    CREATE USER :login@:host IDENTIFIED BY :password;
                    REVOKE ALL PRIVILEGES ON information_schema.* FROM :login@:host;
                    REVOKE ALL PRIVILEGES ON performance_schema.* FROM :login@:host;
                    FLUSH PRIVILEGES;
                ", [
                    ':login' => $login,
                    ':host' => self::getHost(),
                    ':password' => $password,
                ])
                    ->execute();
                return true;
            }
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
            if (self::hasUserByLogin($login)) {
                Yii::$app->db->createCommand("DROP USER :login@:host;", [
                    ':login' => $login,
                    ':host' => self::getHost(),
                ])
                    ->execute();
            }

            return true;
        } catch(\Exception $e) {
            throw $e;
        } catch(\Throwable $e) {
            throw $e;
        }
        
        return false;
    }

    /**
     * Grants the user privileges to the database.
     * 
     * @param string $login User login.
     * @param string $db Database name.
     * 
     * @return bool Returns `true` if privileges were successfully granted.
     * 
     * @throws Exception|Throwable Throws an exception if an error occurs while granting privileges.
     */
    public static function grantPrivileges(string $login, string $db): bool
    {
        try {
            if (self::hasDatabase($db)) {
                $db = Yii::$app->db->quoteTableName($db);
                Yii::$app->db->createCommand("
                    GRANT SELECT, INSERT, UPDATE, DELETE, CREATE, INDEX, ALTER ON $db.* TO :login@:host;
                    FLUSH PRIVILEGES;
                ", [
                    ':login' => $login,
                    ':host' => self::getHost(),
                ])
                    ->execute();
                return true;
            }
        } catch(\Exception $e) {
            throw $e;
        } catch(\Throwable $e) {
            throw $e;
        }

        return false;
    }

    /**
     * Revokes all the user's privileges on the database.
     * 
     * @param string $login user login.
     * @param string $db database name.
     * 
     * @return bool returns `true` if the privileges were successfully revoked.
     * 
     * @throws Exception|Throwable throws an exception if an error occurs while revoking privileges.
     */
    public static function revokePrivileges(string $login, string $db): bool
    {
        try {
            if (self::hasDatabase($db)) {
                $db = Yii::$app->db->quoteTableName($db);
                Yii::$app->db->createCommand("
                    REVOKE ALL PRIVILEGES ON $db.* FROM :login@:host;
                    FLUSH PRIVILEGES;
                ", [
                    ':login' => $login,
                    ':host' => self::getHost(),
                ])
                    ->execute();
                return true;
            }
        } catch(\Exception $e) {
            throw $e;
        } catch(\Throwable $e) {
            throw $e;
        }

        return false;
    }

    /**
     * Getting the hostname.
     */
    public static function getHost()
    {
        preg_match('/host=([^;]+)/', Yii::$app->db->dsn, $matches);
        return $matches[1];
    }

    public static function hasUserByLogin(string $login)
    {
        try {
            return (new Query())
                ->from('mysql.user')
                ->where(['User' => $login])
                ->exists();
        } catch (\Exception $e) {
            return false;
        }
    }

    public static function hasDatabase(string $dbName)
    {
        try {
            $databases = Yii::$app->db->createCommand('SHOW DATABASES')->queryColumn();
            return in_array($dbName, $databases);
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * Checks for the unique value of this attribute.
     * 
     * @param string $class the class in which the check takes place
     * @param string $attr the name of the attribute that is being checked
     * @param mixed $value the value that is being checked
     * 
     * @return bool
     */
    public static function isUniqueValue(string $class, string $attr, mixed $value): bool
    {
        return !$class::find()
            ->where([$attr => $value])
            ->exists();
    }
}