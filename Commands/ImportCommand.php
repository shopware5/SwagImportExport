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
use SwagImportExport\Models\Profile as ProfileEntity;
use SwagImportExport\Models\ProfileRepository;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ImportCommand extends ShopwareCommand
{
    private ?string $profile = null;

    private ?Profile $profileEntity;

    private ?string $format = null;

    private string $filePath;

    private ProfileFactory $profileFactory;

    private ProfileRepository $profileRepository;

    private SessionService $sessionService;

    private ImportServiceInterface $importService;

    public function __construct(
        ProfileFactory $profileFactory,
        ProfileRepository $profileRepository,
        SessionService $sessionService,
        ImportServiceInterface $importService
    ) {
        $this->profileFactory = $profileFactory;
        $this->profileRepository = $profileRepository;

        parent::__construct();
        $this->sessionService = $sessionService;
        $this->importService = $importService;
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

        // validate profile
        if (!$this->profileEntity instanceof Profile) {
            throw new \InvalidArgumentException(\sprintf('Invalid profile: \'%s\'!', $this->profile));
        }

        if (!\is_string($this->format)) {
            throw new \InvalidArgumentException('Format could not be determined');
        }

        $this->start($output, $this->profileEntity, $this->filePath, $this->format);

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
            'batchSize' => $profileModel->getType() === DataDbAdapter::PRODUCT_IMAGE_ADAPTER ? 1 : 50,
        ]);

        $output->writeln('<info>' . \sprintf('Using profile: %s.', $profileModel->getName()) . '</info>');
        $output->writeln('<info>' . \sprintf('Using format: %s.', $format) . '</info>');
        $output->writeln('<info>' . \sprintf('Using file: %s.', $file) . '</info>');

        $count = $this->importService->prepareImport($importRequest);

        $output->writeln('<info>' . \sprintf('Total count: %d.', $count) . '</info>');

        foreach ($this->importService->import($importRequest, $session) as [$profileName, $position]) {
            $output->writeln('<info>' . \sprintf('Processed %s: %d.', $profileName, $position) . '</info>');
        }
    }

    private function prepareImportInputValidation(InputInterface $input): void
    {
        $this->profile = $input->getOption('profile');
        $this->format = $input->getOption('format');
        $this->filePath = $input->getArgument('filepath');

        // if no profile is specified try to find it from the filename
        if ($this->profile === null) {
            $profile = $this->profileFactory->loadProfileByFileName($this->filePath);

            if (!$profile instanceof Profile) {
                throw new \InvalidArgumentException(sprintf('Profile could not be determinated by file path %s.', $this->filePath));
            }

            $this->profileEntity = $profile;
        } else {
            $profile = $this->profileRepository->findOneBy(['name' => $this->profile]);

            if (!$profile instanceof ProfileEntity) {
                throw new \InvalidArgumentException(sprintf('Profile was not found by the name %s', $this->profile));
            }

            $this->profileEntity = new Profile($profile);
        }

        // if no format is specified try to find it from the filename
        if (empty($this->format)) {
            $this->format = \pathinfo($this->filePath, \PATHINFO_EXTENSION);
        }

        // format should be case-insensitive
        $this->format = \strtolower($this->format);

        // validate type
        if (!\in_array($this->format, ['csv', 'xml'])) {
            throw new \InvalidArgumentException(\sprintf('Invalid format: \'%s\'! Valid formats are: CSV and XML.', $this->format));
        }

        // validate path
        if (!\file_exists($this->filePath)) {
            throw new \InvalidArgumentException(\sprintf('File \'%s\' not found!', $this->filePath));
        }
    }
}
