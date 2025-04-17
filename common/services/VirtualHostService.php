<?php

namespace common\services;

use common\models\Modules;
use common\models\Students;
use Exception;
use Yii;

class VirtualHostService
{
    public string $logFile = '';
    private string $vhostPath = '/etc/apache2/sites-available';

    public function __construct()
    {
        $this->logFile = Yii::getAlias('@logs') . '/vhost_setup.log';
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

    public function setupVirtualHost(string $path)
    {
        $path = rtrim($path, '/');
        $titleDir = basename($path);
        $vhostConfig = $this->getVhostConfig($path, $titleDir);
        $vhostFile = "{$this->vhostPath}/{$titleDir}.conf";

        $commandWrite = sprintf('echo %s | sudo /bin/tee %s 2>&1', escapeshellarg($vhostConfig), escapeshellarg($vhostFile));
        
        $output = shell_exec($commandWrite);
        if ($output === null) {
            throw new Exception("Failed to write virtual host config to {$vhostFile}: {$output}");
        }

        $output = shell_exec("sudo ".Yii::getAlias('@bash')."/enable_vhost.sh {$titleDir} {$this->logFile} 2>&1");
        if ($output) {
            throw new Exception("Failed to enable virtual host: {$output}");
        }

        return true;
    }
}