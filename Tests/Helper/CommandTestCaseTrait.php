<?php
declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport\Tests\Helper;

use Shopware\Components\Console\Application;
use Shopware\Components\DependencyInjection\Container;
use SwagImportExport\Commands\ExportCommand;
use SwagImportExport\Commands\ImportCommand;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\BufferedOutput;

trait CommandTestCaseTrait
{
    /**
     * @var array<string>
     */
    private array $files = [];

    abstract public function getContainer(): Container;

    /**
     * @after
     */
    protected function removeCreatedFilesAfter(): void
    {
        foreach ($this->files as $filePath) {
            \unlink($filePath);
        }
    }

    /**
     * @return array<string>
     */
    protected function runCommand(string $command): array
    {
        $this->getContainer()->reset(ExportCommand::class);
        $this->getContainer()->reset(ImportCommand::class);

        $application = new Application($this->getContainer()->get('kernel'));
        $application->setAutoExit(true);

        $input = new StringInput($command);
        $output = new BufferedOutput();

        $application->doRun($input, $output);

        return \explode(\PHP_EOL, $output->fetch());
    }

    private function addCreatedExportFile(string $file): void
    {
        $this->files[] = $this->getFilePath($file);
    }

    private function getFilePath(string $fileName): string
    {
        return Shopware()->DocPath() . $fileName;
    }
}
