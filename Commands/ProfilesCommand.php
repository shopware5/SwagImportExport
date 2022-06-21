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
use Shopware\Components\Model\ModelManager;
use SwagImportExport\CustomModels\ProfileRepository;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ProfilesCommand extends ShopwareCommand
{
    private ProfileRepository $profileRepository;

    private ModelManager $entityManager;

    public function __construct(
        ProfileRepository $profileRepository,
        ModelManager $entityManager
    ) {
        $this->profileRepository = $profileRepository;
        $this->entityManager = $entityManager;

        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this->setName('sw:importexport:profiles')
            ->setDescription('Show all profiles.')
            ->setHelp('The <info>%command.name%</info> shows all Import/Export profiles.');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->registerErrorHandler($output);

        $query = $this->profileRepository->getProfilesListQuery()->getQuery();

        $count = $this->entityManager->getQueryCount($query);

        $output->writeln('<info>' . \sprintf('Total count: %d.', $count) . '</info>');

        $data = $query->getArrayResult();
        foreach ($data as $profile) {
            $output->writeln(
                '<info>'
                . \sprintf("\tProfile %d: '%s', type: %s", $profile['id'], $profile['name'], $profile['type'])
                . '</info>'
            );
        }
    }
}
