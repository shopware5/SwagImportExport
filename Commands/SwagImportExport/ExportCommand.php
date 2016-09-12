<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Shopware\Commands\SwagImportExport;

use Exception;
use Shopware\Commands\ShopwareCommand;
use Shopware\CustomModels\ImportExport\Profile;
use Shopware\CustomModels\ImportExport\Repository;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Shopware\Components\SwagImportExport\Utils\CommandHelper;

class ExportCommand extends ShopwareCommand
{
    protected $profile;

    /**
     * @var Profile
     */
    protected $profileEntity;
    protected $exportVariants;
    protected $limit;
    protected $offset;
    protected $format;
    protected $filePath;
    protected $category;
    protected $sessionId;

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('sw:importexport:export')
            ->setDescription('Export data to files.')
            ->addArgument('filepath', InputArgument::REQUIRED, 'Path to file to read from.')
            ->addOption('profile', 'p', InputOption::VALUE_REQUIRED, 'Which profile will be used?')
            ->addOption('format', 'f', InputOption::VALUE_OPTIONAL, 'What is the format of the imported file - XML or CSV?')
            ->addOption('exportVariants', 'x', InputOption::VALUE_NONE, 'Should the variants be exported?')
            ->addOption('offset', 'o', InputOption::VALUE_OPTIONAL, 'What is the offset?')
            ->addOption('limit', 'l', InputOption::VALUE_OPTIONAL, 'What is the limit?')
            ->addOption('category', 'c', InputOption::VALUE_OPTIONAL, 'Provide a category ID')
            ->setHelp("The <info>%command.name%</info> imports data from a file.");
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
            array(
                'profileEntity' => $this->profileEntity,
                'filePath' => Shopware()->DocPath() . $this->filePath,
                'format' => $this->format,
                'exportVariants' => $this->exportVariants,
                'limit' => $this->limit,
                'offset' => $this->offset,
                'username' => 'Commandline',
                'category' => $this->category ? array( $this->category ) : null
            )
        );

        $output->writeln('<info>' . sprintf("Using profile: %s.", $this->profile) . '</info>');
        $output->writeln('<info>' . sprintf("Using format: %s.", $this->format) . '</info>');
        $output->writeln('<info>' . sprintf("Using file: %s.", $this->filePath) . '</info>');
        if ($this->category) {
            $output->writeln('<info>' . sprintf("Using category as filter: %s.", $this->category) . '</info>');
        }

        $return = $helper->prepareExport();
        $count = $return['count'];
        $output->writeln('<info>' . sprintf("Total count: %d.", $count) . '</info>');

        $data = $helper->exportAction();
        $position = $data['position'];
        $output->writeln('<info>' . sprintf("Processed: %d.", $position) . '</info>');

        while ($position < $count) {
            $data = $helper->exportAction();
            $position = $data['position'];
            $output->writeln('<info>' . sprintf("Processed: %d.", $position) . '</info>');
        }
    }

    /**
     * @param InputInterface $input
     * @throws Exception
     */
    protected function prepareExportInputValidation(InputInterface $input)
    {
        $this->profile = $input->getOption('profile');
        $this->format = $input->getOption('format');
        $this->exportVariants = $input->getOption('exportVariants');
        $this->offset = $input->getOption('offset');
        $this->limit = $input->getOption('limit');
        $this->filePath = $input->getArgument('filepath');
        $this->category = $input->getOption('category');

        if (!$this->filePath) {
            throw new \Exception("File path is required.");
        }

        $parts = explode('.', $this->filePath);

        // get some service from container (formerly Shopware()->Bootstrap()->getResource())
        $em = $this->container->get('models');

        /** @var Repository $profileRepository */
        $profileRepository = $em->getRepository('Shopware\CustomModels\ImportExport\Profile');

        // if no profile is specified try to find it from the filename
        if ($this->profile === null) {
            foreach ($parts as $part) {
                $part = strtolower($part);
                $this->profileEntity = $profileRepository->findOneBy(array('name' => $part));
                if ($this->profileEntity !== null) {
                    $this->profile = $part;
                    break;
                }
            }
        } else {
            $this->profileEntity = $profileRepository->findOneBy(array('name' => $this->profile));
            $this->validateProfiles($input);
        }

        // if no format is specified try to find it from the filename
        if (empty($this->format)) {
            $this->format = pathinfo($this->filePath, PATHINFO_EXTENSION);
        }

        // format should be case insensitive
        $this->format = strtolower($this->format);

        // validate type
        if (!in_array($this->format, array('csv', 'xml'))) {
            throw new Exception(sprintf('Invalid format: \'%s\'! Valid formats are: CSV and XML.', $this->format));
        }
    }

    /**
     * @param InputInterface $input
     */
    protected function validateProfiles(InputInterface $input)
    {
        if (!$this->profileEntity) {
            throw new Exception(sprintf('Invalid profile: \'%s\'!', $this->profile));
        }

        if ($this->profileEntity->getType() != 'articles' && $input->getOption('exportVariants')) {
            throw new \InvalidArgumentException('You can only export variants when exporting the articles profile type.');
        }

        if ($this->profileEntity->getType() == 'articlesImages') {
            throw new \InvalidArgumentException("articlesImages profile type is not supported at the moment.");
        }
    }
}
