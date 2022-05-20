<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport\Tests\Helper;

use Shopware\Components\Console\Application;
use Shopware\Components\DependencyInjection\Container;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\StreamOutput;

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
     * @return array<mixed>
     */
    protected function runCommand(string $command): array
    {
        $application = new Application($this->getContainer()->get('kernel'));
        $application->setAutoExit(true);

        $fp = \tmpfile();
        $input = new StringInput($command);
        $output = new StreamOutput($fp);

        $application->doRun($input, $output);

        $consoleOutput = $this->readConsoleOutput($fp);

        return \explode(\PHP_EOL, $consoleOutput);
    }

    private function addCreatedExportFile(string $file): void
    {
        $this->files[] = $this->getFilePath($file);
    }

    private function getFilePath(string $fileName): string
    {
        return Shopware()->DocPath() . $fileName;
    }

    private function readConsoleOutput($fp): string
    {
        \fseek($fp, 0);
        $output = '';
        while (!\feof($fp)) {
            $output = \fread($fp, 4096);
        }
        \fclose($fp);

        if (!\is_string($output)) {
            throw new \Exception(sprintf('Could not read filepath %s', $fp));
        }

        return $output;
    }
}
