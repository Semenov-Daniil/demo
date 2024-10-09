<?php

namespace app\rbac;

use app\models\Roles;
use Yii;
use yii\rbac\Rule;

/**
 * Checks if user role matches
 */
class UserRoleRule extends Rule
{
    public $name = 'userRole';

    /**
     * Executes the rule.
     *
     * @param string|int $user the user ID. This should be either an integer or a string representing
     * the unique identifier of a user. See [[\yii\web\User::id]].
     * @param Item $item the role or permission that this rule is associated with
     * @param array $params parameters passed to [[CheckAccessInterface::checkAccess()]].
     * @return bool a value indicating whether the rule permits the auth item it is associated with.
     */
    public function execute($user, $item, $params)
    {
        if (!Yii::$app->user->isGuest) {
            $role = Yii::$app->user->identity->roles_id;
            return $role == Roles::getRoleId($item->name);
        }
        return false;
    }
}
