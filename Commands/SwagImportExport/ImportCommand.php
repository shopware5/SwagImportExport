<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Shopware\Commands\SwagImportExport;

use Shopware\Commands\ShopwareCommand;
use Shopware\Components\Model\ModelManager;
use Shopware\Components\SwagImportExport\Profile\Profile;
use Shopware\Components\SwagImportExport\UploadPathProvider;
use Shopware\Components\SwagImportExport\Utils\CommandHelper;
use Shopware\CustomModels\ImportExport\Profile as ProfileEntity;
use Shopware\CustomModels\ImportExport\Repository;
use Shopware_Plugins_Backend_SwagImportExport_Bootstrap as SwagImportExport_Bootstrap;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ImportCommand extends ShopwareCommand
{
    /** @var string */
    protected $profile;

    /** @var ProfileEntity */
    protected $profileEntity;

    /** @var string */
    protected $format;

    /** @var string */
    protected $filePath;

    /** @var int */
    protected $sessionId;

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('sw:importexport:import')
            ->setDescription('Import data from files.')
            ->addArgument('filepath', InputArgument::REQUIRED, 'Path to file to read from.')
            ->addOption('profile', 'p', InputOption::VALUE_REQUIRED, 'Which profile will be used?', null)
            ->addOption('format', 'f', InputOption::VALUE_REQUIRED, 'What is the format of the imported file - XML or CSV?', null)
            ->setHelp('The <info>%command.name%</info> imports data from a file.');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->prepareImportInputValidation($input);

        $this->registerErrorHandler($output);

        $this->start($output, $this->profileEntity, $this->filePath, $this->format);

        $profilesMapper = ['articles', 'articlesImages'];

        /** @var UploadPathProvider $uploadPathProvider */
        $uploadPathProvider = $this->container->get('swag_import_export.upload_path_provider');

        //loops the unprocessed data
        $pathInfo = \pathinfo($this->filePath);
        foreach ($profilesMapper as $profileName) {
            $tmpFile = $uploadPathProvider->getRealPath(
                $pathInfo['basename'] . '-' . $profileName . '-tmp.csv'
            );
            if (\file_exists($tmpFile)) {
                $outputFile = \str_replace('-tmp', '-swag', $tmpFile);
                \rename($tmpFile, $outputFile);

                /** @var Profile $profile */
                $profile = $this->getPlugin()->getProfileFactory()->loadHiddenProfile($profileName);
                /** @var ProfileEntity $profileEntity */
                $profileEntity = $profile->getEntity();

                $this->start($output, $profileEntity, $outputFile, 'csv');
            }
        }
    }

    /**
     * @param ProfileEntity $profileModel
     * @param string        $file
     * @param string        $format
     */
    protected function start(OutputInterface $output, $profileModel, $file, $format)
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
            $this->container->get('models')->clear();
            $position = $data['data']['position'];
            $output->writeln('<info>' . \sprintf('Processed: %d.', $position) . '</info>');
        }
    }

    /**
     * @throws \Exception
     */
    protected function prepareImportInputValidation(InputInterface $input)
    {
        $this->profile = $input->getOption('profile');
        $this->format = $input->getOption('format');
        $this->filePath = $input->getArgument('filepath');

        $parts = \explode('.', $this->filePath);

        /** @var ModelManager $em */
        $em = $this->container->get('models');

        /** @var Repository $profileRepository */
        $profileRepository = $em->getRepository(ProfileEntity::class);

        // if no profile is specified try to find it from the filename
        if ($this->profile === null) {
            foreach ($parts as $part) {
                $part = \strtolower($part);
                $this->profileEntity = $profileRepository->findOneBy(['name' => $part]);
                if ($this->profileEntity !== null) {
                    $this->profile = $part;
                    break;
                }
            }
        } else {
            $this->profileEntity = $profileRepository->findOneBy(['name' => $this->profile]);
        }

        // validate profile
        if (!$this->profileEntity) {
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

    /**
     * @return SwagImportExport_Bootstrap
     */
    private function getPlugin()
    {
        return Shopware()->Plugins()->Backend()->SwagImportExport();
    }
}
