<?php

namespace common\components;

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
    public static function createDirectory(string $path): bool
    {
        return !empty($path) && !is_dir($path) && FileHelper::createDirectory($path, 0755, true);
    }

    /**
     * Removes a directory (and all its content) recursively.
     * 
     * @param string $path path of the directory to be created. 
     * @return void
     */
    public static function removeDirectory(string $path): void
    {
        if (!empty($path) && is_dir($path)) {
            FileHelper::removeDirectory($path);
        }
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

    public static function clearDirectory(string $path, bool $delete = true): bool
    {
        $realPath = realpath($path);
        if (!$realPath || strpos($realPath, Yii::getAlias('@common')) !== 0) {
            return false;
        }

        if (!empty($path) && is_dir($path)) {
            $files = array_diff(scandir($path), array('.', '..'));

            
            foreach ($files as $file) {
                if (!self::clearDirectory(realpath($path) . '/' . $file)) {
                    return false;
                }
            }

            return $delete ? rmdir($path) : true;
        } else if (is_file($path) === true) {
            return $delete ? unlink($path) : true;
        }

        return false;
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
}