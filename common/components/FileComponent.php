<?php

namespace common\components;

use Exception;
use Yii;
use yii\base\BootstrapInterface;
use yii\base\Component;
use yii\helpers\FileHelper;
use yii\helpers\VarDumper;

class FileComponent extends Component implements BootstrapInterface
{
    const VALID_UNITS = [
        'b' => 1,
        'k' => 1024,
        'm' => 1024 ** 2,
        'g' => 1024 ** 3,
    ];

    public array $directories = [];

    public function bootstrap($app)
    {
        foreach ($this->directories as $directory) {
            $this->createDirectory(Yii::getAlias($directory));
        }
    }

    /**
     * Creates a new directory.
     * 
     * @param string $path path of the directory to be created. 
     * @return bool whether the directory is created successfully
     */
    public static function createDirectory(string $path, int $mode = 0755): bool
    {
        try {
            return !empty($path) && !is_dir($path) && FileHelper::createDirectory($path, $mode, true);
        } catch (Exception $e) {
            throw new Exception("\nFailed create directory '$path':\n$e\n");
        }
        return false;
    }

    /**
     * Removes a directory (and all its content) recursively.
     * 
     * @param string $path path of the directory to be created. 
     * @return bool
     */
    public static function removeDirectory(string $path): bool
    {
        try {
            if (!file_exists($path) || !is_dir($path)) {
                return false;
            }
            FileHelper::removeDirectory($path);
            return true;
        } catch (\Exception $e) {
            throw new \Exception("\nFailed remove '$path':\n$e\n");
        }
        return false;
    }

    /**
     * Deletes the file at the specified path.
     * 
     * @param string $path the path to the file. 
     * 
     * @return bool `true` on success or `false` on failure.
     */
    public static function deleteFile(string $path): bool
    {
        if (file_exists($path)) {
            return unlink($path);
        }

        return true;
    }

    public static function clearDirectory(string $path): bool
    {
        try {
            if (!is_dir($path)) throw new \InvalidArgumentException("Directory does not exist: $path");

            $items = scandir($path);
            if ($items === false) {
                return false;
            }

            foreach ($items as $item) {
                if ($item === '.' || $item === '..') {
                    continue;
                }

                $fullPath = $path . DIRECTORY_SEPARATOR . $item;

                if (is_dir($fullPath)) {
                    if (!self::clearDirectory($fullPath) || !rmdir($fullPath)) return false;
                } else {
                    if (!unlink($fullPath)) return false;
                }
            }

            return true;
        } catch (Exception $e) {
            Yii::error("\nFailed to clear the path '$path':\n{$e->getMessage()}", __METHOD__);
            return false;
        }
    }

    public static function updatePermission(string $path, string $rule, string $owner, string $logFile = "")
    {
        $output = Yii::$app->commandComponent->executeBashScript(Yii::getAlias('@bash/utils/update_permissions.sh'), [$path, $rule, $owner, "--log={$logFile}"]);

        if ($output['returnCode']) {
            throw new Exception("\nFailed to update permissions for directory '{$path}':\n{$output['stderr']}\n");
        }

        return true;
    }

    /**
     * Returns the maximum file size that can be uploaded to the server, in the specified unit.
     * 
     * @param string $dataUnit the unit of measure in which you want to return the result.
     *                         - 'b' - bytes
     *                         - 'k' - kilobytes
     *                         - 'm' - megabytes
     *                         - 'g' - gigabytes
     * @return int|float returns the maximum file size in the specified unit.
     */
    public static function getMaxSizeFiles(string $dataUnit = 'b'): int|float
    {
        $result = 0;

        if (!isset(self::VALID_UNITS[$dataUnit])) {
            return $result;
        }

        $input = trim(ini_get('upload_max_filesize'));
        $value = (float)substr($input, 0, -1);
        $unit = strtolower(substr($input, -1));

        if (is_numeric($input)) {
            return (int)$input;
        }

        if (!isset(self::VALID_UNITS[$unit])) {
            return $result;
        }

        $sizeInBytes = $value * self::VALID_UNITS[$unit];

        return $sizeInBytes / self::VALID_UNITS[$dataUnit];
    }

    public static function formatSizeUnits($bytes, $precision = 2)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB'];

        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1000));
        $pow = min($pow, count($units) - 1);

        $bytes /= pow(1000, $pow);

        return round($bytes, $precision) . ' ' . $units[$pow];
    }

    public static function sanitizeFileName(string $string): string
    {
        $invalidChars = ['<', '>', ':', '"', '/', '\\', '|', '?', '*'];
        $string = str_replace($invalidChars, '', $string);
        $string = preg_replace('/[\x00-\x1F]/', '', $string);
        $words = explode(' ', trim($string));
        $words = array_filter($words, function($word) {
            return $word !== '';
        });
        $words = array_map('ucfirst', $words);
        return implode('', $words);
    }
}