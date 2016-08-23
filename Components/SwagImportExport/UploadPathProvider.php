<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Shopware\Components\SwagImportExport;

class UploadPathProvider
{
    const DIR = 'files/import_export';
    const CRON_DIR = 'files/import_cron';

    /**
     * @var string
     */
    private $rootPath;

    /**
     * @param string $rootPath - Root path to shopware
     */
    public function __construct($rootPath)
    {
        $this->rootPath = $rootPath;
    }

    /**
     * Returns the absolute file path with file name.
     *
     * @param string $fileName
     * @param string $directory
     * @return string
     */
    public function getRealPath($fileName, $directory = self::DIR)
    {
        return $this->getPath($directory) . '/' . $fileName;
    }

    /**
     * @param string $path
     * @return string
     */
    public function getFileNameFromPath($path)
    {
        return pathinfo($path, PATHINFO_BASENAME);
    }

    /**
     * @param string $path
     * @return string
     */
    public function getFileExtension($path)
    {
        return pathinfo($path, PATHINFO_EXTENSION);
    }

    /**
     * Return the path to the upload directory.
     *
     * @param string $directory
     * @return string
     */
    public function getPath($directory = self::DIR)
    {
        if ($directory == self::CRON_DIR) {
            return $this->rootPath . self::CRON_DIR;
        }
        return $this->rootPath . self::DIR;
    }
}
