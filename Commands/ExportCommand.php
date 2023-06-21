<?php

declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport\Commands;

use Doctrine\DBAL\Connection;
use Shopware\Commands\ShopwareCommand;
use Shopware\Components\Model\ModelManager;
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

    private Profile $profileEntity;#

    private bool $exportVariants = false;

    private int $limit;

    private int $offset;

    private ?string $format = null;

    private string $filePath;

    private ?int $orderstate = null;

    private ?string $dateFrom = null;

    private ?string $dateTo = null;

    private ?int $categoryId = null;

    private ?int $customerStreamId = null;

    private ?int $productStreamId = null;

    private ProfileRepository $profileRepository;

    private ModelManager $entityManager;

    private string $path;

    private SessionService $sessionService;

    private ProfileFactory $profileFactory;

    private LoggerInterface $logger;

    private ExportServiceInterface $exportService;

    private \Shopware_Components_Config $config;

    private Connection $connection;

    private UploadPathProvider $uploadPathProvider;

    public function __construct(
        ProfileRepository $profileRepository,
        ProfileFactory $profileFactory,
        ModelManager $entityManager,
        SessionService $sessionService,
        string $path,
        LoggerInterface $logger,
        ExportServiceInterface $exportService,
        \Shopware_Components_Config $config,
        Connection $connection,
        UploadPathProvider $uploadPathProvider
    ) {
        $this->profileRepository = $profileRepository;
        $this->entityManager = $entityManager;
        $this->path = $path;
        $this->sessionService = $sessionService;
        $this->profileFactory = $profileFactory;
        $this->logger = $logger;
        $this->exportService = $exportService;
        $this->config = $config;
        $this->connection = $connection;
        $this->uploadPathProvider = $uploadPathProvider;

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
            ->addOption('orderstate', 'orderstate', InputOption::VALUE_OPTIONAL, 'Orderstate ID')
            ->addOption('dateFrom', 'from', InputOption::VALUE_OPTIONAL, 'Date from')
            ->addOption('dateTo', 'to', InputOption::VALUE_OPTIONAL, 'Date to')
            ->addOption('category', 'c', InputOption::VALUE_OPTIONAL, 'Provide a category ID')
            ->addOption('productStream', null, InputOption::VALUE_OPTIONAL, 'Provide a Product-Stream ID or name')
            ->setHelp('The <info>%command.name%</info> exports data to a file.');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // Validation of user input
        $this->prepareExportInputValidation($input);

        $this->filePath = $this->path . \DIRECTORY_SEPARATOR . $this->filePath;

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
                'orderstate' => $this->orderstate,
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
        $output->writeln('<info>' . \sprintf('Using format: %s.', $this->format) . '</info>');
        $output->writeln('<info>' . \sprintf('Using file: %s.', $this->filePath) . '</info>');

        if ($this->categoryId) {
            $output->writeln('<info>' . \sprintf('Using category as filter: %s.', $this->categoryId) . '</info>');
        } elseif ($this->productStreamId) {
            $output->writeln('<info>' . \sprintf('Using Product Stream as filter: %s.', $this->productStreamId) . '</info>');
        }
        if ($this->customerStreamId) {
            $output->writeln('<info>' . \sprintf('Using Customer Stream as filter: %d.', $this->customerStreamId) . '</info>');
        }

        if ($this->orderstate) {
            $output->writeln('<info>' . \sprintf('OrderState: %s.', $this->orderstate) . '</info>');
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
                throw new \InvalidArgumentException('Option "category" must be a valid ID');
            }
        }

        $productStream = $input->getOption('productStream');
        if ($productStream !== null) {
            if (is_numeric($productStream)) {
                $this->productStreamId = (int) $productStream;
            } else {
                $this->productStreamId = $this->getProductStreamIdByName($productStream);
            }
        }

        $orderstate = $input->getOption('orderstate');
        if ($orderstate !== null) {
            if (is_numeric($orderstate)) {
                $this->orderstate = (int) $orderstate;
            } else {
                throw new \InvalidArgumentException('Option "orderstate" must be a valid ID');
            }
        }

        $dateFrom = $input->getOption('dateFrom');
        if (!empty($dateFrom)) {
            try {
                $this->dateFrom = (new \DateTime($dateFrom))->format('Y-m-d H:i:s');
            } catch (\Exception $e) {
                throw new \InvalidArgumentException(\sprintf('Invalid format for "from" date! %s', $e->getMessage()));
            }
        }

        $dateTo = $input->getOption('dateTo');
        if (!empty($dateTo)) {
            try {
                $this->dateTo = (new \DateTime($dateTo))->setTime(23, 59, 59)->format('Y-m-d H:i:s');
            } catch (\Exception $e) {
                throw new \InvalidArgumentException(\sprintf('Invalid format for "to" date! %s', $e->getMessage()));
            }
        }

        if (!empty($this->dateFrom) && !empty($this->dateTo) && $this->dateFrom > $this->dateTo) {
            throw new \InvalidArgumentException('"From" date must be smaller than "to" date');
        }

        $this->filePath = $input->getArgument('filepath');

        $this->profile = $input->getOption('profile');
        // if no profile is specified try to find it from the filename
        if ($this->profile === null) {
            $profile = $this->profileFactory->loadProfileByFileName($this->filePath);

            if (!$profile instanceof Profile) {
                throw new \InvalidArgumentException(sprintf('Profile could not be determinated by file path "%s".', $this->filePath));
            }

            $this->profileEntity = $profile;
            $this->profile = $profile->getName();
        } else {
            $profile = $this->profileRepository->findOneBy(['name' => $this->profile]);

            if (!$profile instanceof ProfileModel) {
                throw new \InvalidArgumentException(sprintf('Profile not found by name "%s".', $this->profile));
            }

            $this->profileEntity = new Profile($profile);
        }

        $this->validateProfiles();

        $customerStreamId = $input->getOption('customerstream');
        if ($customerStreamId !== null) {
            if (is_numeric($customerStreamId)) {
                $this->customerStreamId = (int) $customerStreamId;
                $customerStream = $this->entityManager->find(CustomerStream::class, $this->customerStreamId);
                $this->validateCustomerStream($customerStream);
            } else {
                throw new \InvalidArgumentException('Option "customerstream" must be a valid ID');
            }
        }

        $this->format = $input->getOption('format');
        // if no format is specified try to find it from the filename
        if (empty($this->format)) {
            $this->format = $this->uploadPathProvider->getFileExtension($this->filePath);
        }

        // format should be case-insensitive
        $this->format = \strtolower($this->format);

        // validate type
        if (!\in_array($this->format, ['csv', 'xml'])) {
            throw new \InvalidArgumentException(\sprintf('Invalid file format: "%s"! Valid file formats are: CSV and XML.', $this->format));
        }
    }

    private function validateProfiles(): void
    {
        if ($this->exportVariants && $this->profileEntity->getType() !== DataDbAdapter::PRODUCT_ADAPTER) {
            throw new \InvalidArgumentException('You can only export variants when exporting the articles profile type.');
        }

        if ($this->profileEntity->getType() === DataDbAdapter::PRODUCT_IMAGE_ADAPTER) {
            throw new \InvalidArgumentException('articlesImages profile type is not supported at the moment.');
        }
    }

    private function validateCustomerStream(?CustomerStream $customerStream): void
    {
        if (!$customerStream) {
            throw new \InvalidArgumentException(\sprintf('Invalid stream: "%s"! There is no customer stream with this id.', $this->customerStreamId));
        }

        if (!\in_array($this->profileEntity->getType(), [DataDbAdapter::CUSTOMER_ADAPTER, DataDbAdapter::ADDRESS_ADAPTER], true)) {
            throw new \RuntimeException(\sprintf('Customer stream export can not be used with profile: "%s"!', $this->profile));
        }
    }

    private function getProductStreamIdByName(string $productStreamName): int
    {
        $productStreams = $this->connection->createQueryBuilder()
            ->select('id, name')
            ->from('s_product_streams')
            ->where('name LIKE :productStreamName')
            ->setParameter('productStreamName', sprintf('%%%s%%', $productStreamName))
            ->execute()
            ->fetchAllKeyValue();

        $idAmount = \count($productStreams);
        if ($idAmount > 1) {
            $foundStreams = "\n\n";
            foreach ($productStreams as $productStreamId => $productStreamNameFound) {
                $foundStreams .= sprintf("- %s (ID: %d)\n", $productStreamNameFound, $productStreamId);
            }
            $foundStreams .= "\n";

            throw new \InvalidArgumentException(\sprintf(
                'There are %d streams with the name "%s"%sPlease specify more or use the ID.',
                $idAmount,
                $productStreamName,
                $foundStreams
            ));
        }

        if ($idAmount < 1) {
            throw new \InvalidArgumentException(\sprintf('There are no streams with the name: %s', $productStreamName));
        }

        return (int) array_key_first($productStreams);
    }
}
