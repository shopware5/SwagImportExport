<?php

namespace Shopware\Commands\SwagImportExport;

use Shopware\Commands\ShopwareCommand;
use Shopware\CustomModels\ImportExport\Repository;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ProfilesCommand extends ShopwareCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('sw:importexport:profiles')
            ->setDescription('Show all profiles.')
            ->setHelp("The <info>%command.name%</info> shows all Import/Export profiles.");
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->registerErrorHandler($output);
        
        $em = $this->container->get('models');
        /** @var Repository $profileRepository */
        $profileRepository = $em->getRepository('Shopware\CustomModels\ImportExport\Profile');
        
        $query = $profileRepository->getProfilesListQuery()->getQuery();

        $count = $em->getQueryCount($query);
        
        $output->writeln('<info>' . sprintf("Total count: %d.", $count) . '</info>');

        $data = $query->getArrayResult();
        foreach ($data as $profile) {
            $output->writeln(
                '<info>'
                . sprintf("\tProfile %d: '%s', type: %s", $profile['id'], $profile['name'], $profile['type'])
                . '</info>'
            );
        }
    }
}
