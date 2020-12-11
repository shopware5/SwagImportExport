<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport\Tests\Helper\DataProvider;

use Shopware\Components\Model\ModelManager;
use Shopware\Models\Newsletter\Address;
use Shopware\Models\Newsletter\Group;

class NewsletterDataProvider
{
    /**
     * @var ModelManager
     */
    private $modelManager;

    public function __construct(ModelManager $modelManager)
    {
        $this->modelManager = $modelManager;
    }

    public function createNewsletterDemoData()
    {
        $newsletterGroup = $this->modelManager->find(Group::class, 1);

        for ($addressAmount = 0; $addressAmount < 25; ++$addressAmount) {
            $address = new Address();
            $address->setEmail(\uniqid('test_', true) . '@example.com');
            $address->setAdded(new \DateTime());
            $address->setNewsletterGroup($newsletterGroup);
            $address->setIsCustomer(false);

            $this->modelManager->persist($address);
        }

        $this->modelManager->flush();
    }
}
