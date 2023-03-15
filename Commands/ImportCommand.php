<?php
declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport\Commands;

use Shopware\Commands\ShopwareCommand;
use SwagImportExport\Components\DbAdapters\DataDbAdapter;
use SwagImportExport\Components\Factories\ProfileFactory;
use SwagImportExport\Components\Profile\Profile;
use SwagImportExport\Components\Service\ImportServiceInterface;
use SwagImportExport\Components\Session\SessionService;
use SwagImportExport\Components\Structs\ImportRequest;
use SwagImportExport\Components\UploadPathProvider;
use SwagImportExport\Models\Profile as ProfileEntity;
use SwagImportExport\Models\ProfileRepository;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ImportCommand extends ShopwareCommand
{
    private Profile $profile;

    private ?string $format = null;

    private string $filePath;

    private ProfileFactory $profileFactory;

    private ProfileRepository $profileRepository;

    private SessionService $sessionService;

    private ImportServiceInterface $importService;

    private \Shopware_Components_Config $config;

    private UploadPathProvider $uploadPathProvider;

    public function __construct(
        ProfileFactory $profileFactory,
        ProfileRepository $profileRepository,
        SessionService $sessionService,
        ImportServiceInterface $importService,
        \Shopware_Components_Config $config,
        UploadPathProvider $uploadPathProvider
    ) {
        $this->profileFactory = $profileFactory;
        $this->profileRepository = $profileRepository;
        $this->sessionService = $sessionService;
        $this->importService = $importService;
        $this->config = $config;
        $this->uploadPathProvider = $uploadPathProvider;

        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this->setName('sw:importexport:import')
            ->setDescription('Import data from files.')
            ->addArgument('filepath', InputArgument::REQUIRED, 'Path to file to read from.')
            ->addOption('profile', 'p', InputOption::VALUE_REQUIRED, 'Which profile will be used?')
            ->addOption('format', 'f', InputOption::VALUE_REQUIRED, 'What is the format of the imported file - XML or CSV?')
            ->setHelp('The <info>%command.name%</info> imports data from a file.');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->prepareImportInputValidation($input);

        if (!\is_string($this->format)) {
            throw new \InvalidArgumentException('Should not happen. Format could not be determined');
        }

        $this->start($output, $this->profile, $this->filePath, $this->format);

        return 0;
    }

    private function start(OutputInterface $output, Profile $profileModel, string $file, string $format): void
    {
        $session = $this->sessionService->createSession();

        $profile = $this->profileFactory->loadProfile($profileModel->getId());

        $importRequest = new ImportRequest();
        $importRequest->setData([
            'profileEntity' => $profile,
            'inputFile' => $file,
            'format' => $format,
            'username' => 'Commandline',
            'batchSize' => $profileModel->getType() === DataDbAdapter::PRODUCT_IMAGE_ADAPTER ? 1 : (int) $this->config->getByNamespace('SwagImportExport', 'batch-size-import', 50),
        ]);

        $output->writeln('<info>' . \sprintf('Using profile: %s.', $profileModel->getName()) . '</info>');
        $output->writeln('<info>' . \sprintf('Using format: %s.', $format) . '</info>');
        $output->writeln('<info>' . \sprintf('Using file: %s.', $file) . '</info>');

        $count = $this->importService->prepareImport($importRequest);

        $output->writeln('<info>' . \sprintf('Total count: %d.', $count) . '</info>');

        foreach ($this->importService->import($importRequest, $session) as [$profileName, $position]) {
            $output->writeln('<info>' . \sprintf('Processed %s: %d.', $profileName, $position) . '</info>');
        }

        if (str_ends_with($importRequest->inputFile, ImportServiceInterface::UNPROCESSED_DATA_FILE_ENDING)) {
            unlink($importRequest->inputFile);
        }

        $importRequest->inputFile = $this->filePath;
        $unprocessedData = $this->importService->prepareImportOfUnprocessedData($importRequest);
        if (!\is_array($unprocessedData)) {
            return;
        }

        $output->writeln('<info>Start to import unprocessed data</info>');

        $subProfileModel = $this->profileFactory->loadProfile($unprocessedData['profileId']);
        $subFile = $this->uploadPathProvider->getRealPath($unprocessedData['importFile']);
        $this->start($output, $subProfileModel, $subFile, 'csv');
    }

    private function prepareImportInputValidation(InputInterface $input): void
    {
        $profileName = $input->getOption('profile');
        $this->format = $input->getOption('format');
        $this->filePath = $input->getArgument('filepath');

        // if no profile is specified try to find it from the filename
        if ($profileName === null) {
            $profile = $this->profileFactory->loadProfileByFileName($this->filePath);

            if (!$profile instanceof Profile) {
                throw new \InvalidArgumentException(sprintf('Profile could not be determinated by file path "%s".', $this->filePath));
            }

            $this->profile = $profile;
        } else {
            $profileEntity = $this->profileRepository->findOneBy(['name' => $profileName]);

            if (!$profileEntity instanceof ProfileEntity) {
                throw new \InvalidArgumentException(sprintf('Profile not found by name "%s".', $profileName));
            }

            $this->profile = new Profile($profileEntity);
        }

        // if no format is specified try to find it from the filename
        if (empty($this->format)) {
            $this->format = \pathinfo($this->filePath, \PATHINFO_EXTENSION);
        }

        // format should be case-insensitive
        $this->format = \strtolower($this->format);

        // validate type
        if (!\in_array($this->format, ['csv', 'xml'], true)) {
            throw new \InvalidArgumentException(\sprintf('Invalid file format: "%s"! Valid file formats are: CSV and XML.', $this->format));
        }

        // validate path
        if (!\file_exists($this->filePath)) {
            throw new \InvalidArgumentException(\sprintf('File "%s" not found!', $this->filePath));
        }
    }
}
