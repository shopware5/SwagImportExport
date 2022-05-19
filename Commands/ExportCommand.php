<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport\Commands;

use Doctrine\ORM\EntityManagerInterface;
use Shopware\Commands\ShopwareCommand;
use Shopware\Components\SwagImportExport\Utils\CommandHelper;
use Shopware\CustomModels\ImportExport\Profile;
use Shopware\CustomModels\ImportExport\ProfileRepository;
use Shopware\Models\CustomerStream\CustomerStream;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ExportCommand extends ShopwareCommand
{
    /**
     * @var string
     */
    protected $profile;

    /**
     * @var Profile|null
     */
    protected $profileEntity;

    /**
     * @var string
     */
    protected $exportVariants;

    /**
     * @var int
     */
    protected $limit;

    /**
     * @var int
     */
    protected $offset;

    /**
     * @var string
     */
    protected $format;

    /**
     * @var string
     */
    protected $filePath;

    /**
     * @var \DateTimeInterface|null
     */
    protected $dateFrom;

    /**
     * @var \DateTimeInterface|null
     */
    protected $dateTo;

    /**
     * @var string
     */
    protected $category;

    /**
     * @var int
     */
    protected $sessionId;

    /**
     * @var int
     */
    protected $customerStream;

    /**
     * @var int
     */
    private $productStream;

    private ProfileRepository $profileRepository;

    private EntityManagerInterface $entityManager;

    private string $path;

    public function __construct(
        ProfileRepository $profileRepository,
        EntityManagerInterface $entityManager,
        string $path
    ) {
        $this->profileRepository = $profileRepository;
        $this->entityManager = $entityManager;

        parent::__construct();
        $this->path = $path;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
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
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // Validation of user input
        $this->prepareExportInputValidation($input);

        $this->registerErrorHandler($output);

        $helper = new CommandHelper(
            [
                'profileEntity' => $this->profileEntity,
                'filePath' => $this->path . \DIRECTORY_SEPARATOR . $this->filePath,
                'customerStream' => $this->customerStream,
                'format' => $this->format,
                'exportVariants' => $this->exportVariants,
                'limit' => $this->limit,
                'offset' => $this->offset,
                'dateFrom' => $this->dateFrom,
                'dateTo' => $this->dateTo,
                'filter' => [],
                'username' => 'Commandline',
                'category' => $this->category ? [$this->category] : null,
                'productStream' => $this->productStream ? [$this->productStream] : null,
            ]
        );

        $output->writeln('<info>' . \sprintf('Using profile: %s.', $this->profile) . '</info>');
        if ($this->customerStream) {
            $output->writeln('<info>' . \sprintf('Using customer stream: %d.', $this->customerStream) . '</info>');
        }
        $output->writeln('<info>' . \sprintf('Using format: %s.', $this->format) . '</info>');
        $output->writeln('<info>' . \sprintf('Using file: %s.', $this->filePath) . '</info>');
        if ($this->category) {
            $output->writeln('<info>' . \sprintf('Using category as filter: %s.', $this->category) . '</info>');
        } elseif ($this->productStream) {
            $output->writeln('<info>' . \sprintf('Using Product-Stream as filter: %s.', $this->productStream) . '</info>');
        }

        if ($this->dateFrom) {
            $output->writeln('<info>' . \sprintf('from: %s.', $this->dateFrom->format('d.m.Y H:i:s')) . '</info>');
        }

        if ($this->dateTo) {
            $output->writeln('<info>' . \sprintf('to: %s.', $this->dateTo->format('d.m.Y H:i:s')) . '</info>');
        }

        $preparationData = $helper->prepareExport();
        $count = $preparationData['count'];
        $output->writeln('<info>' . \sprintf('Total count: %d.', $count) . '</info>');

        $position = 0;

        while ($position < $count) {
            $data = $helper->exportAction();
            $position = $data['position'];
            $output->writeln('<info>' . \sprintf('Processed: %d.', $position) . '</info>');
        }
    }

    /**
     * @throws \RuntimeException
     */
    protected function prepareExportInputValidation(InputInterface $input)
    {
        $this->profile = $input->getOption('profile');
        $this->customerStream = $input->getOption('customerstream');
        $this->format = $input->getOption('format');
        $this->exportVariants = $input->getOption('exportVariants');
        $this->offset = (int) $input->getOption('offset');
        $this->limit = (int) $input->getOption('limit');
        $this->filePath = $input->getArgument('filepath');
        $this->category = $input->getOption('category');
        $this->productStream = $input->getOption('productStream');
        $this->dateFrom = $input->getOption('dateFrom');
        $this->dateTo = $input->getOption('dateTo');

        if (!empty($this->dateFrom)) {
            try {
                $this->dateFrom = new \DateTime($this->dateFrom);
            } catch (\Exception $e) {
                throw new \RuntimeException(\sprintf('Invalid format! %s', $e->getMessage()));
            }
        }

        if (!empty($this->dateTo)) {
            try {
                $this->dateTo = new \DateTime($this->dateTo);
                $this->dateTo->setTime(23, 59, 59);
            } catch (\Exception $e) {
                throw new \RuntimeException(\sprintf('Invalid format! %s', $e->getMessage()));
            }
        }

        if (!empty($this->dateFrom) && !empty($this->dateTo)) {
            if ($this->dateFrom > $this->dateTo) {
                throw new \RuntimeException(\sprintf('from date must be greater than to date'));
            }
        }

        if (!$this->filePath) {
            throw new \RuntimeException('File path is required.');
        }

        $parts = \explode('.', $this->filePath);

        // if no profile is specified try to find it from the filename
        if ($this->profile === null) {
            foreach ($parts as $part) {
                $part = \strtolower($part);
                $this->profileEntity = $this->profileRepository->findOneBy(['name' => $part]);
                if ($this->profileEntity !== null) {
                    $this->profile = $part;
                    break;
                }
            }
        } else {
            $this->profileEntity = $this->profileRepository->findOneBy(['name' => $this->profile]);
            $this->validateProfiles($input);
        }

        if (!empty($this->customerStream)) {
            $customerStream = $this->entityManager->find(CustomerStream::class, $this->customerStream);
            $this->validateCustomerStream($customerStream);
        }

        // if no format is specified try to find it from the filename
        if (empty($this->format)) {
            $this->format = \pathinfo($this->filePath, \PATHINFO_EXTENSION);
        }

        // format should be case insensitive
        $this->format = \strtolower($this->format);

        // validate type
        if (!\in_array($this->format, ['csv', 'xml'])) {
            throw new \RuntimeException(\sprintf('Invalid format: \'%s\'! Valid formats are: CSV and XML.', $this->format));
        }
    }

    /**
     * @throws \RuntimeException
     * @throws \InvalidArgumentException
     */
    protected function validateProfiles(InputInterface $input)
    {
        if (!$this->profileEntity) {
            throw new \RuntimeException(\sprintf('Invalid profile: \'%s\'!', $this->profile));
        }

        if ($this->profileEntity->getType() !== 'articles' && $input->getOption('exportVariants')) {
            throw new \InvalidArgumentException('You can only export variants when exporting the articles profile type.');
        }

        if ($this->profileEntity->getType() === 'articlesImages') {
            throw new \InvalidArgumentException('articlesImages profile type is not supported at the moment.');
        }
    }

    /**
     * @param CustomerStream|null $customerStream
     *
     * @throws \RuntimeException
     */
    protected function validateCustomerStream($customerStream)
    {
        if (!$customerStream) {
            throw new \RuntimeException(\sprintf('Invalid stream: \'%s\'! There is no customer stream with this id.', $this->customerStream));
        }

        if (!$this->profileEntity instanceof Profile || !\in_array($this->profileEntity->getType(), ['customers', 'addresses'], true)) {
            throw new \RuntimeException(\sprintf('Customer stream export can not be used with profile: \'%s\'!', $this->profile));
        }
    }
}
