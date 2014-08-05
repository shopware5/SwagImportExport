<?php

namespace Shopware\Commands\SwagImportExport;

use Shopware\Commands\ShopwareCommand;
use Shopware\Components\Model\ModelManager;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ImportArticlesCommand extends AbstractCommand
{

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('import:articles')
                ->setDescription('Import articles.')
                ->prepareImportDefaultConfig()
                ->setHelp("The <info>%command.name%</info> imports articles from a file.");
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
        
        $prep = $this->prepareImport($input, $output);
        $output->writeln('<info>' . sprintf("Total count: %d.", $prep['count']) . '</info>');
        $prep = $this->importAction($input, $output);
        $output->writeln('<info>' . sprintf("Total count: %s.", print_r($prep, 1)) . '</info>');
    }

}
