<?php
declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport\Tests\Functional\Components\DbAdapters;

use PHPUnit\Framework\TestCase;
use Shopware\Tests\Functional\Traits\DatabaseTransactionBehaviour;
use SwagImportExport\Components\DbAdapters\NewsletterDbAdapter;
use SwagImportExport\Tests\Helper\ContainerTrait;

class NewsletterDbAdapterTest extends TestCase
{
    use DatabaseTransactionBehaviour;
    use ContainerTrait;

    public function testWriteThrowsExceptionIfRecordsAreEmpty(): void
    {
        $newsletterDbAdapter = $this->getNewsletterAdapter();

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Es wurden keine Newsletter gefunden.');
        $newsletterDbAdapter->write([]);
    }

    public function testShouldAddCustomerAsNewsletterRecipient(): void
    {
        $newsletterDbAdapter = $this->getNewsletterAdapter();
        $customerData = [
            'default' => [
                [
                    'email' => 'test@example.com',
                    'userID' => 1,
                ],
            ],
        ];

        $newsletterDbAdapter->write($customerData);

        $createdRecipient = $this->getContainer()->get('dbal_connection')->executeQuery("SELECT * FROM s_campaigns_mailaddresses WHERE email='test@example.com'")->fetchAllAssociative();

        static::assertEquals($customerData['default'][0]['email'], $createdRecipient[0]['email']);
    }

    public function testWriteShouldCreateNewsletterRecipient(): void
    {
        $newsletterDbAdapter = $this->getNewsletterAdapter();

        $notExistingRecipient = ['default' => [
            [
                'email' => 'email_address_which_does_not_exist@example.org',
            ],
        ]];

        $newsletterDbAdapter->write($notExistingRecipient);

        $createdRecipient = $this->getContainer()->get('dbal_connection')->executeQuery("SELECT * FROM s_campaigns_mailaddresses WHERE email='email_address_which_does_not_exist@example.org'")->fetchAllAssociative();

        static::assertEquals($notExistingRecipient['default'][0]['email'], $createdRecipient[0]['email']);
    }

    public function testWriteShouldCreateNewsletterRecipientContactData(): void
    {
        $newsletterDBAdapter = $this->getNewsletterAdapter();

        $recipientWithContactData = ['default' => [
            [
                'email' => 'email_address_which_does_not_exists@example.org',
                'firstname' => 'Test',
                'lastname' => 'Recipient',
                'zipcode' => '12345',
            ],
        ]];

        $newsletterDBAdapter->write($recipientWithContactData);

        $createdRecipient = $this->getContainer()->get('dbal_connection')->executeQuery("SELECT * FROM s_campaigns_maildata WHERE email='email_address_which_does_not_exists@example.org'")->fetchAllAssociative();

        static::assertEquals($recipientWithContactData['default'][0]['firstname'], $createdRecipient[0]['firstname']);
        static::assertEquals($recipientWithContactData['default'][0]['lastname'], $createdRecipient[0]['lastname']);
        static::assertEquals($recipientWithContactData['default'][0]['zipcode'], $createdRecipient[0]['zipcode']);
    }

    public function testWriteShouldCreateNewsletterGroup(): void
    {
        $newsletterDbAdapter = $this->getNewsletterAdapter();

        $recipientWithNewNewsletterGroup = ['default' => [
            [
                'email' => 'email_which_does_not_exists@example.org',
                'groupName' => 'New newsletter group',
            ],
        ]];

        $newsletterDbAdapter->write($recipientWithNewNewsletterGroup);

        $createdNewsletterGroup = $this->getContainer()->get('dbal_connection')->executeQuery("SELECT * FROM s_campaigns_groups WHERE name='New newsletter group'")->fetchAllAssociative();

        static::assertEquals('New newsletter group', $createdNewsletterGroup[0]['name']);
    }

    public function testWriteShouldIgnoreExistingCustomerRegisteredInCustomerGroup(): void
    {
        $newsletterDbAdapter = $this->getNewsletterAdapter();
        $dbalConnection = $this->getContainer()->get('dbal_connection');
        $now = (new \DateTime())->format('Y-m-d H:i:s');

        // register existing demo customer to newsletter
        $dbalConnection->executeQuery("INSERT INTO s_campaigns_mailaddresses (customer, email, added) VALUES (1, 'test@example.com', ?)", [$now]);

        $record = [
            'default' => [
                [
                    'email' => 'test@example.com',
                    'groupName' => '',
                ],
            ],
        ];

        $newsletterDbAdapter->write($record);
        $groupCount = $dbalConnection->executeQuery('SELECT COUNT(*) FROM s_campaigns_groups')->fetchOne();

        // check that no new group is created
        static::assertEquals(1, $groupCount);
    }

    private function getNewsletterAdapter(): NewsletterDbAdapter
    {
        return $this->getContainer()->get(NewsletterDbAdapter::class);
    }
}
