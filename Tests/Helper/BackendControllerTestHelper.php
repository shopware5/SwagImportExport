<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport\Tests\Helper;

use SwagImportExport\Tests\Helper\DataProvider\ProfileDataProvider;

class BackendControllerTestHelper
{
    public const EXPECTED_EXPORT_FILES_DIR = __DIR__ . '/ExportFiles';

    private $files = [];

    public function tearDown(): void
    {
        foreach ($this->files as $file) {
            \unlink($file);
        }
    }

    /**
     * @param string $type
     *
     * @return int
     */
    public function getProfileIdByType($type)
    {
        $profileDataProvider = new ProfileDataProvider(
            Shopware()->Container()->get('dbal_connection')
        );

        return $profileDataProvider->getProfileId($type);
    }

    /**
     * @param string $file
     */
    public function addFile($file)
    {
        $this->files[] = $file;
    }
}
