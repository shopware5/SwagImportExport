<?php
declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport\Tests\Functional\Services;

use PHPUnit\Framework\TestCase;
use Shopware\Tests\Functional\Traits\DatabaseTransactionBehaviour;
use SwagImportExport\Components\Service\AutoImportService;
use SwagImportExport\Tests\Helper\ContainerTrait;

class AutoImportServiceTest extends TestCase
{
    use DatabaseTransactionBehaviour;
    use ContainerTrait;

    private array $files = [];

    /**
     * @after
     */
    public function deleteFilesAfter(): void
    {
        foreach ($this->files as $file) {
            if (\file_exists($file)) {
                \unlink($file);
            }
        }

        $this->files = [];
    }

    public function testRunAutoImportThereIsAlreadyAnImportInProgress(): void
    {
        $service = $this->getService();

        $reflectionClass = new \ReflectionClass(AutoImportService::class);

        $property = $reflectionClass->getProperty('directory');
        $property->setAccessible(true);
        $directory = $property->getValue($service);

        $directory .= '/__running';

        $this->files[] = $directory;

        $methodFlagCronAsRunning = $reflectionClass->getMethod('flagCronAsRunning');
        $methodFlagCronAsRunning->setAccessible(true);

        $methodFlagCronAsRunning->invoke($service, $directory);

        $this->expectOutputString('There is already an import in progress.' . \PHP_EOL);
        $service->runAutoImport();
    }

    public function testRunAutoImportNoFilesForImport(): void
    {
        $service = $this->getService();
        $this->expectOutputString('No import files are found.' . \PHP_EOL);
        $service->runAutoImport();
    }

    public function testRunAutoImportShouldThrowNoProfileException(): void
    {
        $service = $this->getService();

        $property = (new \ReflectionClass(AutoImportService::class))->getProperty('directory');
        $property->setAccessible(true);
        $baseDirectory = $property->getValue($service);

        $directory = $baseDirectory . '/no-profile.xml';

        $this->files[] = $directory;
        $this->files[] = $baseDirectory . '/__running';

        $this->installProfile($directory, 'no-content');

        $this->expectOutputString('Kein Profil im Dateinamen no-profile gefunden' . \PHP_EOL);
        $service->runAutoImport();
    }

    public function testRunAutoImportShouldImportFiles(): void
    {
        $config = $this->getContainer()->get(\Shopware_Components_Config::class);
        $previousBatchSize = (int) $config->getByNamespace('SwagImportExport', 'batch-size-import', 50);
        $this->getContainer()->get('config_writer')->save('batch-size-import', 1);
        $this->getContainer()->get(\Zend_Cache_Core::class)->clean();
        $config->setShop(Shopware()->Shop());

        $service = $this->getService();

        $property = (new \ReflectionClass(AutoImportService::class))->getProperty('directory');
        $property->setAccessible(true);
        $baseDirectory = $property->getValue($service);

        $userFile = $baseDirectory . '/default_customers.Shopware.csv';

        $this->files[] = $baseDirectory . '/__running';
        $this->files[] = $userFile;

        $this->installProfile($userFile, (string) \file_get_contents(__DIR__ . '/_fixtures/export.customers.csv'));

        $this->expectOutputString(
            '1 default_customers imported successfully' . \PHP_EOL .
            '2 default_customers imported successfully' . \PHP_EOL .
            '3 default_customers imported successfully' . \PHP_EOL
        );
        $service->runAutoImport();

        $this->getContainer()->get('config_writer')->save('batch-size-import', $previousBatchSize);
        $this->getContainer()->get(\Zend_Cache_Core::class)->clean();
        $config->setShop(Shopware()->Shop());
    }

    private function installProfile(string $filename, string $content): void
    {
        \file_put_contents($filename, $content);
    }

    private function getService(): AutoImportService
    {
        return $this->getContainer()->get(AutoImportService::class);
    }
}
