<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\Helper;

use SwagImportExport\Tests\Helper\DataProvider\ProfileDataProvider;

class BackendControllerTestHelper
{
    const EXPECTED_EXPORT_FILES_DIR = __DIR__ . '/ExportFiles';
    private $files = [];
    /**
     * @var ProfileDataProvider
     */
    private $profileDataProvider;

    /**
     * @param ProfileDataProvider $profileDataProvider
     */
    public function __construct(
        ProfileDataProvider $profileDataProvider
    ) {
        $this->profileDataProvider = $profileDataProvider;
    }

    public function tearDown()
    {
        foreach ($this->files as $file) {
            unlink($file);
        }
    }

    /**
     * @param string $type
     *
     * @return int
     */
    public function getProfileIdByType($type)
    {
        return $this->profileDataProvider->getIdByProfileType($type);
    }

    /**
     * @param string $file
     */
    public function addFile($file)
    {
        $this->files[] = $file;
    }
}
