<?php

namespace app\components;

use Yii;
use yii\base\BaseObject;
use yii\base\InvalidConfigException;
use yii\web\UrlRule;
use yii\web\UrlRuleInterface;

/**
 * A component that defines URL rules based on the assigned role.
 */
class RoleBasedUrlRule extends UrlRule implements UrlRuleInterface
{
    public string $role = '';

    public function parseRequest($manager, $request)
    {
        if (Yii::$app->user->can($this->role) && $parsing_result = parent::parseRequest($manager, $request)) {
            return $parsing_result;
        }
        return false;
    }
}