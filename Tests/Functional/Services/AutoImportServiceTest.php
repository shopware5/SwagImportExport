<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport\Tests\Functional\Services;

use PHPUnit\Framework\TestCase;
use SwagImportExport\Components\Factories\ProfileFactory;
use SwagImportExport\Components\Service\AutoImportService;
use SwagImportExport\Components\UploadPathProvider;
use SwagImportExport\Tests\Helper\ContainerTrait;
use SwagImportExport\Tests\Helper\DatabaseTestCaseTrait;

class AutoImportServiceTest extends TestCase
{
    use DatabaseTestCaseTrait;
    use ContainerTrait;

    private array $files = [];

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
            $this->getContainer()->get('models'),
            $this->getContainer()->get(ProfileFactory::class)
        );
    }
}
