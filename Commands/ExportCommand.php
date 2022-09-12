<?php
declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport\Commands;

use Doctrine\ORM\EntityManagerInterface;
use Shopware\Commands\ShopwareCommand;
use Shopware\Models\CustomerStream\CustomerStream;
use SwagImportExport\Components\DbAdapters\DataDbAdapter;
use SwagImportExport\Components\Factories\ProfileFactory;
use SwagImportExport\Components\Logger\LogDataStruct;
use SwagImportExport\Components\Logger\LoggerInterface;
use SwagImportExport\Components\Profile\Profile;
use SwagImportExport\Components\Service\ExportServiceInterface;
use SwagImportExport\Components\Session\SessionService;
use SwagImportExport\Components\Structs\ExportRequest;
use SwagImportExport\Components\UploadPathProvider;
use SwagImportExport\Components\Utils\FileNameGenerator;
use SwagImportExport\Components\Utils\SnippetsHelper;
use SwagImportExport\Models\Profile as ProfileModel;
use SwagImportExport\Models\ProfileRepository;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ExportCommand extends ShopwareCommand
{
    private ?string $profile = null;

    private Profile $profileEntity;

    private bool $exportVariants = false;

    private int $limit;

    private int $offset;

    private ?string $format = null;

    private string $filePath;

    private ?string $dateFrom = null;

    private ?string $dateTo = null;

    private ?int $categoryId = null;

    private ?int $customerStreamId = null;

    private ?int $productStreamId = null;

    private ProfileRepository $profileRepository;

    private EntityManagerInterface $entityManager;

    private string $path;

    private SessionService $sessionService;

    private UploadPathProvider $uploadPathProvider;

    private ProfileFactory $profileFactory;

    private LoggerInterface $logger;

    private ExportServiceInterface $exportService;

    private \Shopware_Components_Config $config;

    public function __construct(
        ProfileRepository $profileRepository,
        ProfileFactory $profileFactory,
        EntityManagerInterface $entityManager,
        SessionService $sessionService,
        UploadPathProvider $uploadPathProvider,
        string $path,
        LoggerInterface $logger,
        ExportServiceInterface $exportService,
        \Shopware_Components_Config $config
    ) {
        $this->profileRepository = $profileRepository;
        $this->entityManager = $entityManager;
        $this->path = $path;
        $this->sessionService = $sessionService;
        $this->uploadPathProvider = $uploadPathProvider;
        $this->profileFactory = $profileFactory;
        $this->logger = $logger;
        $this->exportService = $exportService;
        $this->config = $config;

        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this->setName('sw:importexport:export')
            ->setDescription('Export data to files.')
            ->addArgument('filepath', InputArgument::REQUIRED, 'Path to file to read from.')
            ->addOption('profile', 'p', InputOption::VALUE_REQUIRED, 'Which profile will be used?')
            ->addOption('customerstream', 'u', InputOption::VALUE_OPTIONAL, 'Which customer stream id?')
            ->addOption('format', 'f', InputOption::VALUE_OPTIONAL, 'What is the format of the exported file - XML or CSV?')
            ->addOption('exportVariants', 'x', InputOption::VALUE_NONE, 'Should the variants be exported?')
            ->addOption('offset', 'o', InputOption::VALUE_OPTIONAL, 'What is the offset?')
            ->addOption('limit', 'l', InputOption::VALUE_OPTIONAL, 'What is the limit?')
            ->addOption('dateFrom', 'from', InputOption::VALUE_OPTIONAL, 'Date from')
            ->addOption('dateTo', 'to', InputOption::VALUE_OPTIONAL, 'Date to')
            ->addOption('category', 'c', InputOption::VALUE_OPTIONAL, 'Provide a category ID')
            ->addOption('productStream', null, InputOption::VALUE_OPTIONAL, 'Provide a Product-Stream ID')
            ->setHelp('The <info>%command.name%</info> exports data to a file.');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // Validation of user input
        $this->prepareExportInputValidation($input);

        if (empty($this->filePath) && $this->format) {
            $this->filePath = $this->uploadPathProvider->getRealPath(FileNameGenerator::generateFileName('export', $this->format, $this->profileEntity->getEntity()));
        } else {
            $this->filePath = $this->path . \DIRECTORY_SEPARATOR . $this->filePath;
        }

        $profile = $this->profileFactory->loadProfile($this->profileEntity->getId());

        $exportRequest = new ExportRequest();
        $exportRequest->setData(
            [
                'profileEntity' => $profile,
                'filePath' => $this->filePath,
                'customerStream' => $this->customerStreamId,
                'format' => $this->format,
                'exportVariants' => $this->exportVariants,
                'limit' => $this->limit,
                'offset' => $this->offset,
                'dateFrom' => $this->dateFrom,
                'dateTo' => $this->dateTo,
                'filter' => [],
                'username' => 'Commandline',
                'category' => $this->categoryId ? [$this->categoryId] : null,
                'productStream' => $this->productStreamId ? [$this->productStreamId] : null,
                'batchSize' => $this->config->getByNamespace('SwagImportExport', 'batch-size-export', 1000),
            ]
        );

        $output->writeln('<info>' . \sprintf('Using profile: %s.', $this->profile) . '</info>');
        if ($this->customerStreamId) {
            $output->writeln('<info>' . \sprintf('Using customer stream: %d.', $this->customerStreamId) . '</info>');
        }
        $output->writeln('<info>' . \sprintf('Using format: %s.', $this->format) . '</info>');
        $output->writeln('<info>' . \sprintf('Using file: %s.', $this->filePath) . '</info>');

        if ($this->categoryId) {
            $output->writeln('<info>' . \sprintf('Using category as filter: %s.', $this->categoryId) . '</info>');
        } elseif ($this->productStreamId) {
            $output->writeln('<info>' . \sprintf('Using Product-Stream as filter: %s.', $this->productStreamId) . '</info>');
        }

        if ($this->dateFrom) {
            $output->writeln('<info>' . \sprintf('from: %s.', $this->dateFrom) . '</info>');
        }

        if ($this->dateTo) {
            $output->writeln('<info>' . \sprintf('to: %s.', $this->dateTo) . '</info>');
        }

        $session = $this->sessionService->createSession();
        $count = $this->exportService->prepareExport($exportRequest, $session);
        $output->writeln('<info>' . \sprintf('Total count: %d.', $count) . '</info>');

        $lastPositions = 0;
        foreach ($this->exportService->export($exportRequest, $session) as $position) {
            $lastPositions = $position;
            $output->writeln('<info>' . \sprintf('Processed: %d.', $position) . '</info>');
        }

        $message = \sprintf(
            '%s %s %s',
            $lastPositions,
            SnippetsHelper::getNamespace('backend/swag_import_export/default_profiles')->get('type/' . $exportRequest->profileEntity->getType()),
            SnippetsHelper::getNamespace('backend/swag_import_export/log')->get('export/success')
        );

        $this->logger->write([$message], 'false', $session);

        $logData = new LogDataStruct(
            \date('Y-m-d H:i:s'),
            $exportRequest->filePath,
            $exportRequest->profileEntity->getName(),
            $message,
            'true'
        );

        $this->logger->writeToFile($logData);

        return 0;
    }

    private function prepareExportInputValidation(InputInterface $input): void
    {
        $this->exportVariants = $input->getOption('exportVariants');
        $this->offset = (int) $input->getOption('offset');
        $this->limit = (int) $input->getOption('limit');

        $categoryId = $input->getOption('category');
        if ($categoryId !== null) {
            if (is_numeric($categoryId)) {
                $this->categoryId = (int) $categoryId;
            } else {
                throw new \RuntimeException('Option "category" must be a valid ID');
            }
        }

        $productStreamId = $input->getOption('productStream');
        if ($productStreamId !== null) {
            if (is_numeric($productStreamId)) {
                $this->productStreamId = (int) $productStreamId;
            } else {
                throw new \RuntimeException('Option "productStream" must be a valid ID');
            }
        }

        $dateFrom = $input->getOption('dateFrom');
        if (!empty($dateFrom)) {
            try {
                $this->dateFrom = (new \DateTime($dateFrom))->format('Y-m-d H:i:s');
            } catch (\Exception $e) {
                throw new \RuntimeException(\sprintf('Invalid format! %s', $e->getMessage()));
            }
        }

        $dateTo = $input->getOption('dateTo');
        if (!empty($dateTo)) {
            try {
                $this->dateTo = (new \DateTime($dateTo))->setTime(23, 59, 59)->format('Y-m-d H:i:s');
            } catch (\Exception $e) {
                throw new \RuntimeException(\sprintf('Invalid format! %s', $e->getMessage()));
            }
        }

        if (!empty($this->dateFrom) && !empty($this->dateTo) && $this->dateFrom > $this->dateTo) {
            throw new \RuntimeException('from date must be greater than to date');
        }

        $this->filePath = $input->getArgument('filepath');
        if (!$this->filePath) {
            throw new \RuntimeException('File path is required.');
        }

        $this->profile = $input->getOption('profile');
        // if no profile is specified try to find it from the filename
        if ($this->profile === null) {
            $profile = $this->profileFactory->loadProfileByFileName($this->filePath);

            if (!$profile instanceof Profile) {
                throw new \InvalidArgumentException(sprintf('Profile could not be determinated by file path %s.', $this->filePath));
            }

            $this->profileEntity = $profile;
        } else {
            $profile = $this->profileRepository->findOneBy(['name' => $this->profile]);

            if (!$profile instanceof ProfileModel) {
                throw new \InvalidArgumentException(sprintf('Profile not found by name %s.', $profile));
            }

            $this->profileEntity = new Profile($profile);
        }

        $this->validateProfiles($input);

        $customerStreamId = $input->getOption('customerstream');
        if ($customerStreamId !== null) {
            if (is_numeric($customerStreamId)) {
                $this->customerStreamId = (int) $customerStreamId;
                $customerStream = $this->entityManager->find(CustomerStream::class, $this->customerStreamId);
                $this->validateCustomerStream($customerStream);
            } else {
                throw new \RuntimeException('Option "productStream" must be a valid ID');
            }
        }

        $this->format = $input->getOption('format');
        // if no format is specified try to find it from the filename
        if (empty($this->format)) {
            $this->format = \pathinfo($this->filePath, \PATHINFO_EXTENSION);
        }

        // format should be case-insensitive
        $this->format = \strtolower($this->format);

        // validate type
        if (!\in_array($this->format, ['csv', 'xml'])) {
            throw new \RuntimeException(\sprintf('Invalid format: \'%s\'! Valid formats are: CSV and XML.', $this->format));
        }
    }

    private function validateProfiles(InputInterface $input): void
    {
        if (!isset($this->profileEntity)) {
            throw new \RuntimeException(\sprintf('Invalid profile: \'%s\'!', $this->profile));
        }

        if ($this->profileEntity->getType() !== DataDbAdapter::PRODUCT_ADAPTER && $this->exportVariants) {
            throw new \InvalidArgumentException('You can only export variants when exporting the articles profile type.');
        }

        if ($this->profileEntity->getType() === DataDbAdapter::PRODUCT_IMAGE_ADAPTER) {
            throw new \InvalidArgumentException('articlesImages profile type is not supported at the moment.');
        }
    }

    private function validateCustomerStream(?CustomerStream $customerStream): void
    {
        if (!$customerStream) {
            throw new \RuntimeException(\sprintf('Invalid stream: \'%s\'! There is no customer stream with this id.', $this->customerStreamId));
        }

        if (!\in_array($this->profileEntity->getType(), [DataDbAdapter::CUSTOMER_ADAPTER, DataDbAdapter::ADDRESS_ADAPTER], true)) {
            throw new \RuntimeException(\sprintf('Customer stream export can not be used with profile: \'%s\'!', $this->profile));
        }
    }
}
