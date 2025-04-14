<?php

namespace common\services;

use common\models\Modules;
use common\models\Students;
use Exception;
use Yii;

class VirtualHostService
{
    private string $dns = 'demo';
    private string $vhostPath = '/etc/apache2/sites-available';

    private function getVhostConfig(string $path, string $title): string
    {
        return 
        "<VirtualHost *:80>
            ServerName {$title}.{$this->dns}
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
        $logFile = Yii::getAlias('@logs') . "/vhost_setup.log";

        if (file_put_contents($vhostFile, $vhostConfig) === false) {
            throw new Exception("Failed to write virtual host config to {$vhostFile}");
        }

        $output = shell_exec("sudo ".Yii::getAlias('@bash')."/enable_vhost.sh {$titleDir} {$logFile} 2>&1");
        if ($output) {
            throw new Exception("Failed to enable virtual host: {$output}");
        }

        return true;
    }
}