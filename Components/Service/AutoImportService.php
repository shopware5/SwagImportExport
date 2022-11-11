<?php
declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport\Components\Service;

use SwagImportExport\Components\DbAdapters\DataDbAdapter;
use SwagImportExport\Components\Factories\ProfileFactory;
use SwagImportExport\Components\Profile\Profile;
use SwagImportExport\Components\Session\SessionService;
use SwagImportExport\Components\Structs\ImportRequest;
use SwagImportExport\Components\UploadPathProvider;
use SwagImportExport\Components\Utils\SnippetsHelper;

class AutoImportService implements AutoImportServiceInterface
{
    private const LOCKED_FILENAME = '__running';

    private UploadPathProvider $uploadPathProvider;

    private ProfileFactory $profileFactory;

    private string $directory;

    private SessionService $sessionService;

    private ImportServiceInterface $importService;

    private \Shopware_Components_Config $config;

    public function __construct(
        UploadPathProvider $uploadPathProvider,
        ProfileFactory $profileFactory,
        SessionService $sessionService,
        ImportServiceInterface $importService,
        \Shopware_Components_Config $config
    ) {
        $this->uploadPathProvider = $uploadPathProvider;
        $this->profileFactory = $profileFactory;
        $this->sessionService = $sessionService;
        $this->directory = $this->uploadPathProvider->getPath(UploadPathProvider::CRON_DIR);
        $this->importService = $importService;
        $this->config = $config;
    }

    public function runAutoImport(): void
    {
        $files = $this->getFiles();
        if (\count($files) === 0) {
            echo 'No import files are found.' . \PHP_EOL;

            return;
        }

        $lockerFileLocation = sprintf('%s/%s', $this->directory, self::LOCKED_FILENAME);

        if (\in_array(self::LOCKED_FILENAME, $files)) {
            $file = \fopen($lockerFileLocation, 'rb');
            if (!\is_resource($file)) {
                echo sprintf('Could not open file at "%s"%s', $lockerFileLocation, \PHP_EOL);

                return;
            }
            $fileContent = (int) \fread($file, (int) \filesize($lockerFileLocation));
            \fclose($file);

            if ($fileContent > \time()) {
                echo sprintf('There is already an import in progress.%s', \PHP_EOL);

                return;
            }
            \unlink($lockerFileLocation);
        }

        $this->flagCronAsRunning($lockerFileLocation);

        $this->importFiles($files, $lockerFileLocation);

        \unlink($lockerFileLocation);
    }

    private function importFiles(array $files, string $lockerFileLocation): void
    {
        foreach ($files as $file) {
            $fileExtension = \strtolower(\pathinfo($file, \PATHINFO_EXTENSION));
            $fileName = \strtolower(\pathinfo($file, \PATHINFO_FILENAME));

            if (\in_array($fileExtension, ['xml', 'csv'])) {
                try {
                    $profile = $this->getProfile($fileName, $file);

                    $mediaPath = $this->uploadPathProvider->getRealPath($file, UploadPathProvider::CRON_DIR);
                } catch (\Exception $e) {
                    echo $e->getMessage() . \PHP_EOL;
                    \unlink($lockerFileLocation);

                    return;
                }

                try {
                    $this->start($profile, $mediaPath, $fileExtension);
                    \unlink($mediaPath);
                } catch (\Exception $e) {
                    // copy file as broken
                    $brokenFilePath = $this->uploadPathProvider->getRealPath('broken-' . $file);
                    \copy($mediaPath, $brokenFilePath);

                    echo $e->getMessage() . \PHP_EOL;
                    \unlink($lockerFileLocation);

                    return;
                }
            }
        }
    }

    private function getProfile(string $fileName, string $file): Profile
    {
        $profile = $this->profileFactory->loadProfileByFileName($file);

        if (!$profile instanceof Profile) {
            $message = SnippetsHelper::getNamespace()->get('cronjob/no_profile', 'No profile found %s');

            throw new \RuntimeException(\sprintf($message, $fileName));
        }

        return $profile;
    }

    /**
     * @return array<string>
     */
    private function getFiles(): array
    {
        $allFiles = \scandir($this->directory);
        if (!\is_array($allFiles)) {
            echo sprintf('Could not scan directory "%s"', $this->directory);

            return [];
        }

        return \array_diff($allFiles, ['.', '..', '.htaccess']);
    }

    /**
     * Create empty file to flag cron as running
     */
    private function flagCronAsRunning(string $lockerFileLocation): void
    {
        $timeout = \time() + 1800;
        $file = \fopen($lockerFileLocation, 'wb');
        if (!\is_resource($file)) {
            echo sprintf('Could not open file at "%s"%s', $lockerFileLocation, \PHP_EOL);

            return;
        }
        \fwrite($file, (string) $timeout);
        \fclose($file);
    }

    private function start(Profile $profileModel, string $inputFile, string $format): void
    {
        $importRequest = new ImportRequest();
        $importRequest->setData(
            [
                'profileEntity' => $profileModel,
                'inputFile' => $inputFile,
                'format' => $format,
                'username' => 'Cron',
                'batchSize' => $profileModel->getType() === DataDbAdapter::PRODUCT_IMAGE_ADAPTER ? 1 : (int) $this->config->getByNamespace('SwagImportExport', 'batch-size-import', 50),
            ]
        );

        $session = $this->sessionService->createSession();

        foreach ($this->importService->import($importRequest, $session) as [$profileName, $position]) {
            echo $position . ' ' . $profileName . ' imported successfully' . \PHP_EOL;
        }
    }
}
