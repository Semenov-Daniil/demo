<?php

namespace app\components;

use Yii;
use yii\base\BaseObject;
use yii\base\InvalidConfigException;
use yii\web\UrlRule;
use yii\web\UrlRuleInterface;

class RoleBasedUrlRule extends UrlRule implements UrlRuleInterface
{
    public string $roles = '';
    public $i;

    public function parseRequest($manager, $request)
    {
        if (Yii::$app->user->can($this->roles) && $parsing_result = parent::parseRequest($manager, $request)) {
            return $parsing_result;
        }
        return false;
    }
}