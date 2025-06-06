<?php

namespace common\services;

use common\models\Modules;
use common\models\Students;
use Exception;
use Yii;

class VirtualHostService
{
    public string $logFile = '';
    private string $domainSuffix = '.demo.ru';

    public function __construct()
    {
        $this->logFile = 'vhost.log';
    }

    public function getDomain(string $main): string
    {
        return "$main{$this->domainSuffix}";
    }

    public function createVirtualHost(string $login, string $module, string $path): bool
    {
        $domain = $this->getDomain($module);
        $output = Yii::$app->commandComponent->executeBashScript(Yii::getAlias('@bash/vhost/create_vhost.sh'), [$login, $domain, $path, "--log={$this->logFile}"]);

        if ($output['returnCode']) {
            throw new Exception("Failed to create virtual host {$domain}: {$output['stderr']}");
        }

        return true;
    }

    public function disableVirtualHost(string $module): bool
    {
        $domain = $this->getDomain($module);

        $output = Yii::$app->commandComponent->executeBashScript(Yii::getAlias('@bash/vhost/disable_vhost.sh'), [$domain, "--log={$this->logFile}"]);

        if ($output['returnCode']) {
            throw new Exception("Failed to disabled virtual host {$domain}: {$output['stderr']}");
        }

        return true;
    }

    public function enableVirtualHost(string $module): bool
    {
        $domain = $this->getDomain($module);

        $output = Yii::$app->commandComponent->executeBashScript(Yii::getAlias('@bash/vhost/enable_vhost.sh'), [$domain, "--log={$this->logFile}"]);

        if ($output['returnCode']) {
            throw new Exception("Failed to enabled virtual host {$domain}: {$output['stderr']}");
        }

        return true;
    }

    public function changeStatusVirtualHost (string $module, bool $status): bool
    {
        return $status
                        ? $this->enableVirtualHost($module)
                        : $this->disableVirtualHost($module);
    }

    public function deleteVirtualHost(string $module): bool
    {
        $domain = $this->getDomain($module);

        $output = Yii::$app->commandComponent->executeBashScript(Yii::getAlias('@bash/vhost/remove_vhost.sh'), [$domain, "--log={$this->logFile}"]);

        if ($output['returnCode']) {
            throw new Exception("Failed to delete virtual host {$domain}:\n{$output['stderr']}\n");
        }

        return true;
    }
}