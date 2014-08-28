<?php

namespace Shopware\Commands\SwagImportExport;

use Shopware\Commands\ShopwareCommand;
use Shopware\Components\Model\ModelManager;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Shopware\Components\SwagImportExport\DataWorkflow;

class ExportCommand extends ShopwareCommand
{

    protected $profile;
    protected $profileEntity;
    protected $format;
    protected $filePath;
    protected $sessionId;

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('sw:export')
                ->setDescription('Export data to files.')
                ->addArgument('filepath', InputArgument::REQUIRED, 'Path to file to read from.')
                ->addOption('profile', 'p', InputOption::VALUE_REQUIRED, 'How many times should the message be printed?', null)
                ->addOption('format', 'f', InputOption::VALUE_REQUIRED, 'What is the format of the imported file - XML or CSV?', null)
                ->addOption('exportVariants', 'ev', InputOption::VALUE_NONE, 'Should the variants be exported?', null)
                ->addOption('offset', 'o', InputOption::VALUE_REQUIRED, 'What is the offset?', null)
                ->addOption('limit', 'l', InputOption::VALUE_REQUIRED, 'What is the limit?', null)
                ->setHelp("The <info>%command.name%</info> imports data from a file.");
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // Validation of user input
        $this->prepareExportInputValidation($input, $output);

        $output->writeln('<info>' . sprintf("Using profile: %s.", $this->profile) . '</info>');
        $output->writeln('<info>' . sprintf("Using format: %s.", $this->format) . '</info>');
        $output->writeln('<info>' . sprintf("Using file: %s.", $this->filePath) . '</info>');

        $return = $this->prepareExport($input, $output);
        $count = $return['count'];
        $output->writeln('<info>' . sprintf("Total count: %d.", $count) . '</info>');

        $data = $this->exportAction($input, $output);
        $this->sessionId = $data['sessionId'];
        $position = $data['position'];
        $output->writeln('<info>' . sprintf("Position: %d.", $position) . '</info>');

        while ($position < $count) {
            $data = $this->exportAction($input, $output);
            $position = $data['position'];
            $output->writeln('<info>' . sprintf("Processed: %d.", $position) . '</info>');
        }
    }

    protected function prepareExportInputValidation(InputInterface $input, OutputInterface $output)
    {
        $this->profile = $input->getOption('profile');
        $this->format = $input->getOption('format');
        $this->exportVariants = $input->getOption('exportVariants');
        $this->offset = $input->getOption('offset');
        $this->limit = $input->getOption('limit');
        $this->filePath = $input->getArgument('filepath');

        $parts = explode('.', $this->filePath);
        $count = count($parts);

        // get some service from container (formerly Shopware()->Bootstrap()->getResource())
        $em = $this->container->get('models');

        $profileRepository = $em->getRepository('Shopware\CustomModels\ImportExport\Profile');

        // if no profile is specified try to find it from the filename
        if ($this->profile === NULL) {
            foreach ($parts as $part) {
                $part = strtolower($part);
                $this->profileEntity = $profileRepository->findOneBy(array('name' => $part));
                if ($this->profileEntity !== NULL) {
                    $this->profile = $part;
                    break;
                }
            }
        } else {
            $this->profileEntity = $profileRepository->findOneBy(array('name' => $this->profile));
        }

        // validate profile
        if (!$this->profileEntity) {
            throw new \Exception(sprintf('Invalid profile: \'%s\'!', $this->profile));
        }

        // if no format is specified try to find it from the filename
        if ($this->format === NULL) {
            $this->format = pathinfo($this->filePath, PATHINFO_EXTENSION);
        }

        // validate type
        if (!in_array($this->format, array('csv', 'xml'))) {
            throw new \Exception(sprintf('Invalid format: \'%s\'!', $this->format));
        }
    }

    protected function prepareExport(InputInterface $input, OutputInterface $output)
    {
        $this->sessionId = null;
        $postData = array(
            'sessionId' => $this->sessionId,
            'profileId' => (int) $this->profileEntity->getId(),
            'type' => 'export',
            'format' => $this->format,
            'filter' => array(),
            'limit' => array(
                'limit' => $this->limit,
                'offset' => $this->offset,
            ),
        );

        if ($this->exportVariants) {
            $postData['filter']['variants'] = $this->exportVariants;
        }

        $profile = $this->Plugin()->getProfileFactory()->loadProfile($postData);

        $dataFactory = $this->Plugin()->getDataFactory();

        $dbAdapter = $dataFactory->createDbAdapter($profile->getType());
        $dataSession = $dataFactory->loadSession($postData);

        $dataIO = $dataFactory->createDataIO($dbAdapter, $dataSession);

        $colOpts = $dataFactory->createColOpts($postData['columnOptions']);
        $limit = $dataFactory->createLimit($postData['limit']);
        $filter = $dataFactory->createFilter($postData['filter']);
        $maxRecordCount = $postData['max_record_count'];
        $type = $postData['type'];
        $format = $postData['format'];

        $dataIO->initialize($colOpts, $limit, $filter, $maxRecordCount, $type, $format);

        $ids = $dataIO->preloadRecordIds()->getRecordIds();

        $position = $dataIO->getSessionPosition();
        $position = $position == null ? 0 : $position;

        return array('position' => $position, 'count' => count($ids));
    }

    public function exportAction(InputInterface $input, OutputInterface $output)
    {
        $postData = array(
            'profileId' => (int) $this->profileEntity->getId(),
            'type' => 'export',
            'format' => $this->format,
            'sessionId' => $this->sessionId,
            'fileName' => basename($this->filePath),
            'filter' => array(),
            'limit' => array(
                'limit' => $this->limit,
                'offset' => $this->offset,
            ),
        );

        if ($this->exportVariants) {
            $postData['filter']['variants'] = $this->exportVariants;
        }

        $profile = $this->Plugin()->getProfileFactory()->loadProfile($postData);

        $dataFactory = $this->Plugin()->getDataFactory();

        $dbAdapter = $dataFactory->createDbAdapter($profile->getType());
        $dataSession = $dataFactory->loadSession($postData);

        //create dataIO
        $dataIO = $dataFactory->createDataIO($dbAdapter, $dataSession);

        $colOpts = $dataFactory->createColOpts($postData['columnOptions']);
        $limit = $dataFactory->createLimit($postData['limit']);
        $filter = $dataFactory->createFilter($postData['filter']);
        $maxRecordCount = $postData['max_record_count'];
        $type = $postData['type'];
        $format = $postData['format'];

        $dataIO->initialize($colOpts, $limit, $filter, $type, $format, $maxRecordCount);

        // we create the file writer that will write (partially) the result file
        $fileFactory = $this->Plugin()->getFileIOFactory();
        $fileHelper = $fileFactory->createFileHelper();
        $fileWriter = $fileFactory->createFileWriter($postData, $fileHelper);

        $dataTransformerChain = $this->Plugin()->getDataTransformerFactory()->createDataTransformerChain(
                $profile, array('isTree' => $fileWriter->hasTreeStructure())
        );

        $dataWorkflow = new DataWorkflow($dataIO, $profile, $dataTransformerChain, $fileWriter);

        $post = $dataWorkflow->export($postData, $this->filePath);

        return $post;
    }

    protected function Plugin()
    {
        return Shopware()->Plugins()->Backend()->SwagImportExport();
    }

}
