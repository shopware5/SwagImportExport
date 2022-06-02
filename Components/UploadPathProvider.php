<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport\Components;

class UploadPathProvider
{
    public const DIR = 'files/import_export';
    public const CRON_DIR = 'files/import_cron';

    private string $rootPath;

    /**
     * @param string $rootPath - Root path to shopware
     */
    public function __construct(string $rootPath)
    {
        $this->rootPath = $rootPath;
    }

    /**
     * Returns the absolute file path with file name.
     */
    public function getRealPath(string $fileName, string $directory = self::DIR): string
    {
        return $this->getPath($directory) . '/' . $fileName;
    }

    public function getFileNameFromPath(string $path): string
    {
        return \pathinfo($path, \PATHINFO_BASENAME);
    }

    public function getFileExtension(string $path): string
    {
        return \pathinfo($path, \PATHINFO_EXTENSION);
    }

    /**
     * Return the path to the upload directory.
     */
    public function getPath(string $directory = self::DIR): string
    {
        if ($directory === self::CRON_DIR) {
            return $this->rootPath . self::CRON_DIR;
        }

        return $this->rootPath . self::DIR;
    }
}
