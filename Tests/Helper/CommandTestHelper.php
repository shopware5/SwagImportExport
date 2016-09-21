<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\Helper;

use Shopware\Components\Console\Application;
use Shopware\Components\Model\ModelManager;
use SwagImportExport\Tests\Helper\DataProvider\NewsletterDataProvider;
use SwagImportExport\Tests\Helper\DataProvider\ProfileDataProvider;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\StreamOutput;

class CommandTestHelper
{
    /**
     * @var array
     */
    private $createdFiles = [];

    const IMPORT_FILES_DIR = __DIR__ . '/ImportFiles/';

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
     * @param ModelManager $modelManager
     * @param ProfileDataProvider $profileDataProvider
     * @param NewsletterDataProvider $newsletterDataProvider
     */
    public function __construct(
        ModelManager $modelManager,
        ProfileDataProvider $profileDataProvider,
        NewsletterDataProvider $newsletterDataProvider
    ) {
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
        foreach ($this->createdFiles as $path) {
            unlink($path);
        }

        $this->modelManager->rollback();
    }

    /**
     * @param string $command
     * @return string|StreamOutput
     */
    public function runCommand($command)
    {
        $application = new Application(Shopware()->Container()->get('kernel'));
        $application->setAutoExit(true);

        $fp = tmpfile();
        $input = new StringInput($command);
        $output = new StreamOutput($fp);

        $application->doRun($input, $output);

        return $this->readConsoleOutput($fp);
    }

    /**
     * @param string $fileName
     * @return string
     */
    public function getFilePath($fileName)
    {
        return Shopware()->DocPath() . $fileName;
    }

    /**
     * @param string $fileName
     */
    public function addFile($fileName)
    {
        $this->createdFiles[] = $this->getFilePath($fileName);
    }

    public function createNewsletterDemoData()
    {
        $this->newsletterDataProvider->createNewsletterDemoData();
    }

    /**
     * @param $fp
     * @return string
     */
    private function readConsoleOutput($fp)
    {
        fseek($fp, 0);
        $output = '';
        while (!feof($fp)) {
            $output = fread($fp, 4096);
        }
        fclose($fp);

        return $output;
    }
}
