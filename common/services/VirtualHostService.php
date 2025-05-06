<?php

namespace common\services;

use common\models\Modules;
use common\models\Students;
use Exception;
use Yii;

class VirtualHostService
{
    public string $logFile = '';

    public function __construct()
    {
        $this->logFile = 'vhost.log';
    }

    private function getVhostConfig(string $path, string $title): string
    {
        return 
        "<VirtualHost *:80>
            ServerName {$title}.".Yii::$app->params['siteName']."
            DocumentRoot {$path}
            <Directory {$path}>
                Options Indexes FollowSymLinks
                AllowOverride All
                Require all granted
            </Directory>
            ErrorLog {$path}/error.log
            CustomLog {$path}/access.log combined
        </VirtualHost>";
    }

    public function createVirtualHost(string $path): bool
    {
        $path = rtrim($path, '/');
        $titleDir = basename($path);
        $vhostConfig = $this->getVhostConfig($path, $titleDir);

        $output = Yii::$app->commandComponent->executeBashScript(Yii::getAlias('@bash/vhost/create_vhost.sh'), [$titleDir, $vhostConfig, "--log={$this->logFile}"]);

        if (!$output['returnCode']) {
            throw new Exception("Failed to create virtual host {$titleDir}: {$output['stderr']}");
        }

        return true;
    }

    public function disableVirtualHost(string $path)
    {
        $path = rtrim($path, '/');
        $titleDir = basename($path);

        $output = Yii::$app->commandComponent->executeBashScript(Yii::getAlias('@bash/vhost/disable_vhost.sh'), [$titleDir, "--log={$this->logFile}"]);

        if (!$output['returnCode']) {
            throw new Exception("Failed to disabled virtual host {$titleDir}: {$output['stderr']}");
        }

        return true;
    }

    public function deleteVirtualHost(string $path)
    {
        $path = rtrim($path, '/');
        $titleDir = basename($path);

        $output = Yii::$app->commandComponent->executeBashScript(Yii::getAlias('@bash/vhost/remove_vhost.sh'), [$titleDir, "--log={$this->logFile}"]);

        if (!$output['returnCode']) {
            throw new Exception("Failed to delete virtual host {$titleDir}: {$output['stderr']}");
        }

        return true;
    }
}