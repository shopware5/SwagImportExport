<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\Helper;

use Shopware\Components\Model\ModelManager;
use Shopware\Components\SwagImportExport\UploadPathProvider;
use SwagImportExport\Tests\Helper\DataProvider\NewsletterDataProvider;
use SwagImportExport\Tests\Helper\DataProvider\ProfileDataProvider;

class BackendControllerTestHelper
{
    private $files = [];

    const EXPECTED_EXPORT_FILES_DIR = __DIR__ . '/ExportFiles';

    /**
     * @var UploadPathProvider
     */
    private $uploadPathProvider;

    /**
     * @var ModelManager
     */
    private $modelManager;

    /**
     * @var ProfileDataProvider
     */
    private $profileDataProvider;

    /**
     * @var NewsletterDataProvider
     */
    private $newsletterDataProvider;

    /**
     * @param UploadPathProvider $uploadPathProvider
     * @param ModelManager $modelManager
     * @param ProfileDataProvider $profileDataProvider
     * @param NewsletterDataProvider $newsletterDataProvider
     */
    public function __construct(
        UploadPathProvider $uploadPathProvider,
        ModelManager $modelManager,
        ProfileDataProvider $profileDataProvider,
        NewsletterDataProvider $newsletterDataProvider
    )
    {
        $this->uploadPathProvider = $uploadPathProvider;
        $this->modelManager = $modelManager;
        $this->profileDataProvider = $profileDataProvider;
        $this->newsletterDataProvider = $newsletterDataProvider;
    }

    public function setUp()
    {
        $this->modelManager->beginTransaction();
        $this->profileDataProvider->createProfiles();
    }

    public function tearDown()
    {
        $this->modelManager->rollback();

        foreach ($this->files as $file) {
            unlink($file);
        }
    }

    public function createNewsletterDemoData()
    {
        $this->newsletterDataProvider->createNewsletterDemoData();
    }

    /**
     * @param string $type
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