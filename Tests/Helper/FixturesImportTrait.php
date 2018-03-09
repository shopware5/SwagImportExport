<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport\Tests\Helper;

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

    /**
     * @before
     */
    protected function importFixtures()
    {
        /** @var ProfileDataProvider $profileDataProvider */
        $profileDataProvider = Shopware()->Container()->get('swag_import_export.tests.profile_data_provider');
        $profileDataProvider->createProfiles();
    }

    private function importNewsletterDemoData()
    {
        $this->modelManager = Shopware()->Container()->get('models');
        $newsletterGroup = $this->modelManager->find(Group::class, 1);

        for ($addressAmount = 0; $addressAmount < 25; ++$addressAmount) {
            $address = new Address();
            $address->setEmail('test_' . $addressAmount . '@example.com');
            $address->setAdded(new \DateTime());
            $address->setNewsletterGroup($newsletterGroup);
            $address->setIsCustomer(false);

            $this->modelManager->persist($address);
        }

        $this->modelManager->flush();
    }
}
