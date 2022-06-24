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
use SwagImportExport\Components\Factories\ProfileFactory;
use SwagImportExport\Components\UploadPathProvider;
use SwagImportExport\Components\Utils\CommandHelper;
use SwagImportExport\CustomModels\Profile as ProfileEntity;
use SwagImportExport\CustomModels\ProfileRepository;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ImportCommand extends ShopwareCommand
{
    protected string $profile;

    protected ?ProfileEntity $profileEntity;

    protected ?string $format = null;

    protected string $filePath;

    private ProfileFactory $profileFactory;

    private UploadPathProvider $uploadPathProvider;

    private EntityManagerInterface $entityManager;

    private ProfileRepository $profileRepository;

    public function __construct(
        ProfileFactory $profileFactory,
        UploadPathProvider $uploadPathProvider,
        EntityManagerInterface $entityManager,
        ProfileRepository $profileRepository
    ) {
        $this->profileFactory = $profileFactory;
        $this->uploadPathProvider = $uploadPathProvider;
        $this->entityManager = $entityManager;
        $this->profileRepository = $profileRepository;

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
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->prepareImportInputValidation($input);

        // validate profile
        if (!$this->profileEntity instanceof ProfileEntity) {
            throw new \Exception(\sprintf('Invalid profile: \'%s\'!', $this->profile));
        }

        if (!\is_string($this->format)) {
            throw new \Exception('Format could not be determined');
        }

        $this->start($output, $this->profileEntity, $this->filePath, $this->format);

        $profilesMapper = ['articles', 'articlesImages'];

        // loops the unprocessed data
        $pathInfo = \pathinfo($this->filePath);
        foreach ($profilesMapper as $profileName) {
            $tmpFile = $this->uploadPathProvider->getRealPath(
                $pathInfo['basename'] . '-' . $profileName . '-tmp.csv'
            );
            if (\file_exists($tmpFile)) {
                $outputFile = \str_replace('-tmp', '-swag', $tmpFile);
                \rename($tmpFile, $outputFile);

                $profile = $this->profileFactory->loadHiddenProfile($profileName);
                $profileEntity = $profile->getEntity();

                $this->start($output, $profileEntity, $outputFile, 'csv');
            }
        }
    }

    protected function start(OutputInterface $output, ProfileEntity $profileModel, string $file, string $format): void
    {
        $helper = new CommandHelper(
            [
                'profileEntity' => $profileModel,
                'filePath' => $file,
                'format' => $format,
                'username' => 'Commandline',
            ]
        );

        $output->writeln('<info>' . \sprintf('Using profile: %s.', $profileModel->getName()) . '</info>');
        $output->writeln('<info>' . \sprintf('Using format: %s.', $format) . '</info>');
        $output->writeln('<info>' . \sprintf('Using file: %s.', $file) . '</info>');

        $preparationData = $helper->prepareImport();
        $count = $preparationData['count'];
        $output->writeln('<info>' . \sprintf('Total count: %d.', $count) . '</info>');

        $position = 0;

        while ($position < $count) {
            $data = $helper->importAction();
            $this->entityManager->clear();
            $position = $data['data']['position'];
            $output->writeln('<info>' . \sprintf('Processed: %d.', $position) . '</info>');
        }
    }

    protected function prepareImportInputValidation(InputInterface $input): void
    {
        $this->profile = $input->getOption('profile');
        $this->format = $input->getOption('format');
        $this->filePath = $input->getArgument('filepath');

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
        }

        // validate profile
        if (!$this->profileEntity instanceof ProfileEntity) {
            throw new \Exception(\sprintf('Invalid profile: \'%s\'!', $this->profile));
        }

        // if no format is specified try to find it from the filename
        if (empty($this->format)) {
            $this->format = \pathinfo($this->filePath, \PATHINFO_EXTENSION);
        }

        // format should be case insensitive
        $this->format = \strtolower($this->format);

        // validate type
        if (!\in_array($this->format, ['csv', 'xml'])) {
            throw new \Exception(\sprintf('Invalid format: \'%s\'! Valid formats are: CSV and XML.', $this->format));
        }

        // validate path
        if (!\file_exists($this->filePath)) {
            throw new \Exception(\sprintf('File \'%s\' not found!', $this->filePath));
        }
    }
}
