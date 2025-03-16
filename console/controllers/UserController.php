<?php

namespace console\controllers;

use common\models\EncryptedPasswords;
use common\models\Events;
use common\models\ExpertsEvents;
use common\models\Users;
use common\traits\RandomStringTrait;
use Yii;
use yii\console\Controller;
use yii\console\ExitCode;
use yii\helpers\Console;

class UserController extends Controller
{
    use RandomStringTrait;

    /**
     * This command creates user expert.
     * 
     * @return int Exit code
     */
    public function actionCreateExpert()
    {
        $surname = $this->prompt('Введите фамилию: ', ['required' => true]);
        $name = $this->prompt('Введите имя: ', ['required' => true]);
        $title = $this->prompt('Введите название события: ', ['required' => true]);
        $countModules = $this->prompt('Введите кол-во модулей: ', ['required' => true, 'validator' => function($input, &$error) {
            if (intval($input) < 1) {
                $error = 'Кол-во модулей должно быть не меньше 1.';
                return false;
            }
            return true;
        }]);

        $transaction = Yii::$app->db->beginTransaction();

        try {
            $user = new Users();
            $user->load([
                'surname' => $surname,
                'name' => $name,
            ], '');

            if ($user->addExpert()) {
                $event = new Events();
                $event->load([
                    'title' => $title,
                    'countModules' => $countModules,
                ], '');
                $event->experts_id = $user->id;
                
                if ($event->save()) {
                    $transaction->commit();
                    $this->stdout("Expert created: $user->login/" . EncryptedPasswords::decryptByPassword(EncryptedPasswords::findOne(['users_id' => $user->id])?->encrypted_password));
                    return ExitCode::OK;
                }
            }

            $this->stderr("Expert not created\n");

            $errors = array_merge($user->errors, $event->errors);
            foreach ($errors as $errorValue) {
                foreach ($errorValue as $error) {
                    $this->stderr("$error\n");
                }
            }

            $transaction->rollBack();
        } catch(\Exception $e) {
            $this->stderr("Expert not created");
            $transaction->rollBack();
            var_dump($e);die;
        } catch(\Throwable $e) {
            $this->stderr("Expert not created");
            $transaction->rollBack();
        }

        return ExitCode::UNSPECIFIED_ERROR;
    }

    public function actionUpdatePasswords()
    {
        $users = Users::find()->all();

        foreach ($users as $user) {
            $transaction = Yii::$app->db->beginTransaction();

            try {
                $temp_password = $this->generateRandomString(6, ['lowercase','uppercase','digits']);
                $user->password = Yii::$app->security->generatePasswordHash($temp_password);

                if ($user->save()) {
                    if (EncryptedPasswords::updateEncryptedPassword($user->id, $temp_password)) {
                        $transaction->commit();
                    }
                }

                $transaction->rollBack();
            } catch(\Exception $e) {
                $this->stderr("Passwords not updated");
                $transaction->rollBack();
                var_dump($e);die;
            } catch(\Throwable $e) {
                $this->stderr("Passwords not updated");
                $transaction->rollBack();
            }
        }

        $this->stderr("Updated password end.\n");
    }
}
