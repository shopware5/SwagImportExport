<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport\Tests\Helper;

use Shopware\Components\DependencyInjection\Container;
use Shopware\Components\Model\ModelManager;
use Shopware\Models\Newsletter\Address;
use Shopware\Models\Newsletter\Group;
use SwagImportExport\Tests\Helper\DataProvider\ProfileDataProvider;

trait FixturesImportTrait
{
    /**
     * @var ModelManager
     */
    private $modelManager;

    abstract public function getContainer(): Container;

    /**
     * @before
     */
    protected function importFixtures(): void
    {
        $profileDataProvider = new ProfileDataProvider(
            $this->getContainer()->get('dbal_connection')
        );

        $profileDataProvider->createProfiles();
    }

    private function importNewsletterDemoData(): void
    {
        $this->modelManager = $this->getContainer()->get('models');
        $newsletterGroup = $this->modelManager->find(Group::class, 1);

        self::assertInstanceOf(Group::class, $newsletterGroup);

        for ($addressAmount = 0; $addressAmount < 25; ++$addressAmount) {
            $address = new Address();
            $address->setEmail('test_' . $addressAmount . '@example.com');
            $address->setAdded(new \DateTime());
            $address->setNewsletterGroup($newsletterGroup);
            $address->setIsCustomer(false);

            $this->modelManager->persist($address);
            $this->modelManager->flush();
        }
    }

    private function addProductStream(): void
    {
        if ($this->isStreamInstalled()) {
            return;
        }

        $connection = $this->getContainer()->get('dbal_connection');

        $sql = <<<SQL
INSERT INTO `s_product_streams` (`id`, `name`, `conditions`, `type`) VALUES
(999999, 'Test', '{"Shopware\\\\\\\Bundle\\\\\\\SearchBundle\\\\\\\Condition\\\\\\\ManufacturerCondition":{"manufacturerIds":[2]}}', 1);
SQL;

        $connection->executeQuery($sql);
    }

    private function isStreamInstalled(): bool
    {
        return (bool) $this->getContainer()->get('dbal_connection')
            ->createQueryBuilder()
            ->select('id')
            ->from('s_product_streams')
            ->where('id = 999999')
            ->execute()
            ->fetch(\PDO::FETCH_COLUMN);
    }
}
