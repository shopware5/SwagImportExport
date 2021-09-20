<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport\Tests\Functional\Service;

use Enlight_Class;
use PHPUnit\Framework\TestCase;
use Shopware\Components\SwagImportExport\Factories\ProfileFactory;
use Shopware\Components\SwagImportExport\Service\AutoImportService;
use Shopware\Components\SwagImportExport\UploadPathProvider;
use SwagImportExport\Tests\Helper\DatabaseTestCaseTrait;

class AutoImportServiceTest extends TestCase
{
    use DatabaseTestCaseTrait;

    /**
     * @var array
     */
    private $files = [];

    /**
     * @after
     */
    public function deleteFilesAfter()
    {
        foreach ($this->files as $file) {
            \file_exists($file) ? \unlink($file) : '';
        }

        $this->files = [];
    }

    public function testRunAutoImportThereIsAlreadyAnImportInProgress()
    {
        $service = $this->getService();

        $reflectionClass = new \ReflectionClass(AutoImportService::class);

        $methodGetDirectory = $reflectionClass->getMethod('getDirectory');
        $methodGetDirectory->setAccessible(true);
        $directory = $methodGetDirectory->invoke($service);

        $directory .= '/__running';

        $this->files[] = $directory;

        $methodFlagCronAsRunning = $reflectionClass->getMethod('flagCronAsRunning');
        $methodFlagCronAsRunning->setAccessible(true);

        $methodFlagCronAsRunning->invoke($service, $directory);

        $this->expectOutputString('There is already an import in progress.' . \PHP_EOL);
        $service->runAutoImport();
    }

    public function testRunAutoImportNoFilesForImport()
    {
        $service = $this->getService();
        $this->expectOutputString('No import files are found.' . \PHP_EOL);
        $service->runAutoImport();
    }

    public function testRunAutoImportShouldThrowNoProfileException()
    {
        $service = $this->getService();

        $reflectionClass = new \ReflectionClass(AutoImportService::class);
        $methodGetDirectory = $reflectionClass->getMethod('getDirectory');
        $methodGetDirectory->setAccessible(true);
        $baseDirectory = $methodGetDirectory->invoke($service);

        $directory = $baseDirectory . '/no-profile.xml';

        $this->files[] = $directory;
        $this->files[] = $baseDirectory . '/__running';

        $this->installProfile($directory, 'no-content');

        $this->expectOutputString('Kein Profil im Dateinamen no-profile gefunden' . \PHP_EOL);
        $service->runAutoImport();
    }

    public function testRunAutoImportShouldImportFiles()
    {
        $service = $this->getService();

        $reflectionClass = new \ReflectionClass(AutoImportService::class);
        $methodGetDirectory = $reflectionClass->getMethod('getDirectory');
        $methodGetDirectory->setAccessible(true);
        $baseDirectory = $methodGetDirectory->invoke($service);

        $userFile = $baseDirectory . '/default_customers.Shopware.csv';

        $this->files[] = $baseDirectory . '/__running';
        $this->files[] = $userFile;

        $this->installProfile($userFile, \file_get_contents(__DIR__ . '/_fixtures/export.customers.csv'));

        $this->expectOutputString('3 customers imported successfully' . \PHP_EOL);
        $service->runAutoImport();
    }

    /**
     * @param string $filename
     */
    private function installProfile($filename, $content)
    {
        \file_put_contents($filename, $content);
    }

    /**
     * @return AutoImportService
     */
    private function getService()
    {
        return new AutoImportService(
            new UploadPathProvider(Shopware()->DocPath()),
            Shopware()->Container()->get('models'),
            Enlight_Class::Instance(ProfileFactory::class)
        );
    }
}
