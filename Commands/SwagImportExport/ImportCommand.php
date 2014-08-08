<?php

namespace Shopware\Commands\SwagImportExport;

use Shopware\Commands\ShopwareCommand;
use Shopware\Components\Model\ModelManager;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Shopware\Components\SwagImportExport\DataWorkflow;

class ImportCommand extends ShopwareCommand
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
        $this->setName('sw:import')
                ->setDescription('Import data from files.')
                ->addArgument('filepath', InputArgument::REQUIRED, 'Path to file to read from.')
                ->addOption('profile', 'p', InputOption::VALUE_REQUIRED, 'How many times should the message be printed?', null)
                ->addOption('format', 'f', InputOption::VALUE_REQUIRED, 'What is the format of the imported file - XML or CSV?', null)
                ->setHelp("The <info>%command.name%</info> imports data from a file.");
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->prepareImportInputValidation($input, $output);

        $output->writeln('<info>' . sprintf("Using profile: %s.", $this->profile) . '</info>');
        $output->writeln('<info>' . sprintf("Using format: %s.", $this->format) . '</info>');
        $output->writeln('<info>' . sprintf("Using file: %s.", $this->filePath) . '</info>');

        $return = $this->prepareImport($input, $output);
        $count = $return['count'];
        $output->writeln('<info>' . sprintf("Total count: %d.", $count) . '</info>');

        $return = $this->importAction($input, $output);
        $this->sessionId = $return['data']['sessionId'];
        $position = $return['data']['position'];
        $output->writeln('<info>' . sprintf("Position: %d.", $position) . '</info>');

        while ($position < $count) {
//            try {
            $return = $this->importAction($input, $output);
//            } catch (\Exception $e) {
//                echo $e->getTrace(), "\n";
//                echo $e->getTraceAsString(), "\n";
//                exit;
//            }
            $position = $return['data']['position'];
            $output->writeln('<info>' . sprintf("Processed: %d.", $position) . '</info>');
        }
    }

    protected function prepareImportInputValidation(InputInterface $input, OutputInterface $output)
    {
        $this->profile = $input->getOption('profile');
        $this->format = $input->getOption('format');
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

        // validate path
        if (!file_exists($this->filePath)) {
            throw new \Exception(sprintf('File \'%s\' not found!', $this->filePath));
        }
    }

    protected function prepareImport(InputInterface $input, OutputInterface $output)
    {
        $this->sessionId = null;
        $postData = array(
            'sessionId' => $this->sessionId,
            'profileId' => (int) $this->profileEntity->getId(),
            'type' => 'import',
            'format' => $this->format,
            'file' => $this->filePath,
        );

        //get file format
        $inputFileName = $postData['file'];

        //get profile type
        $postData['adapter'] = $this->profileEntity->getType();

        // we create the file reader that will read the result file
        $fileReader = $this->Plugin()->getFileIOFactory()->createFileReader($postData, null);

        if ($this->format === 'xml') {
            $tree = json_decode($this->profileEntity->getTree(), true);
            $fileReader->setTree($tree);
        }

        $dataFactory = $this->Plugin()->getDataFactory();

        $dbAdapter = $dataFactory->createDbAdapter($this->profileEntity->getType());
        $dataSession = $dataFactory->loadSession($postData);

        //create dataIO
        $dataIO = $dataFactory->createDataIO($dbAdapter, $dataSession);

        $position = $dataIO->getSessionPosition();
        $position = $position == null ? 0 : $position;

        $totalCount = $fileReader->getTotalCount($inputFileName);

        return array('success' => true, 'position' => $position, 'count' => $totalCount);
    }

    public function importAction(InputInterface $input, OutputInterface $output)
    {
        $postData = array(
            'type' => 'import',
            'profileId' => (int) $this->profileEntity->getId(),
            'importFile' => $this->filePath,
            'sessionId' => $this->sessionId,
            'format' => $this->format,
            'columnOptions' => null,
            'limit' => array(),
            'filter' => null,
            'max_record_count' => null,
        );

        $inputFile = $postData['importFile'];

        // we create the file reader that will read the result file
        $fileReader = $this->Plugin()->getFileIOFactory()->createFileReader($postData, null);

        //load profile
        $profile = $this->Plugin()->getProfileFactory()->loadProfile($postData);

        //get profile type
        $postData['adapter'] = $profile->getType();

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

        $dataTransformerChain = $this->Plugin()->getDataTransformerFactory()->createDataTransformerChain(
                $profile, array('isTree' => $fileReader->hasTreeStructure())
        );

        $dataWorkflow = new DataWorkflow($dataIO, $profile, $dataTransformerChain, $fileReader);

        try {
            $post = $dataWorkflow->import($postData, $inputFile);

            return array('success' => true, 'data' => $post);
        } catch (Exception $e) {
            return array('success' => false, 'msg' => $e->getMessage());
        }
    }

    protected function Plugin()
    {
        return Shopware()->Plugins()->Backend()->SwagImportExport();
    }

}
