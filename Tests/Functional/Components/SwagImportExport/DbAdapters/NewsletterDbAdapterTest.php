<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport\Tests\Functional\Components\SwagImportExport\DbAdapters;

use Doctrine\DBAL\Connection;
use PHPUnit\Framework\TestCase;
use Shopware\Components\SwagImportExport\DbAdapters\NewsletterDbAdapter;
use SwagImportExport\Tests\Helper\ContainerTrait;
use SwagImportExport\Tests\Helper\DatabaseTestCaseTrait;

class NewsletterDbAdapterTest extends TestCase
{
    use DatabaseTestCaseTrait;
    use ContainerTrait;

    public function testWriteThrowsExceptionIfRecordsAreEmpty()
    {
        $newsletterDbAdapter = $this->getNewsletterAdapter();

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Es wurden keine Newsletter gefunden.');
        $newsletterDbAdapter->write([]);
    }

    public function testShouldAddCustomerAsNewsletterRecipient()
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

        /** @var Connection $dbalConnection */
        $dbalConnection = $this->getContainer()->get('dbal_connection');
        $createdRecipient = $dbalConnection->executeQuery("SELECT * FROM s_campaigns_mailaddresses WHERE email='test@example.com'")->fetchAll();

        static::assertEquals($customerData['default'][0]['email'], $createdRecipient[0]['email']);
    }

    public function testWriteShouldCreateNewsletterRecipient()
    {
        $newsletterDbAdapter = $this->getNewsletterAdapter();

        $notExistingRecipient = ['default' => [
            [
                'email' => 'email_address_which_does_not_exist@example.org',
            ],
        ]];

        $newsletterDbAdapter->write($notExistingRecipient);

        /** @var Connection $dbalConnection */
        $dbalConnection = $this->getContainer()->get('dbal_connection');
        $createdRecipient = $dbalConnection->executeQuery("SELECT * FROM s_campaigns_mailaddresses WHERE email='email_address_which_does_not_exist@example.org'")->fetchAll();

        static::assertEquals($notExistingRecipient['default'][0]['email'], $createdRecipient[0]['email']);
    }

    public function testWriteShouldCreateNewsletterRecipientContactData()
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

        /** @var Connection $dbalConnection */
        $dbalConnection = $this->getContainer()->get('dbal_connection');
        $createdRecipient = $dbalConnection->executeQuery("SELECT * FROM s_campaigns_maildata WHERE email='email_address_which_does_not_exists@example.org'")->fetchAll();

        static::assertEquals($recipientWithContactData['default'][0]['firstname'], $createdRecipient[0]['firstname']);
        static::assertEquals($recipientWithContactData['default'][0]['lastname'], $createdRecipient[0]['lastname']);
        static::assertEquals($recipientWithContactData['default'][0]['zipcode'], '12345');
    }

    public function testWriteShouldCreateNewsletterGroup()
    {
        $newsletterDbAdapter = $this->getNewsletterAdapter();

        $recipientWithNewNewsletterGroup = ['default' => [
            [
                'email' => 'email_which_does_not_exists@example.org',
                'groupName' => 'New newsletter group',
            ],
        ]];

        $newsletterDbAdapter->write($recipientWithNewNewsletterGroup);

        /** @var Connection $dbalConnection */
        $dbalConnection = $this->getContainer()->get('dbal_connection');
        $createdNewsletterGroup = $dbalConnection->executeQuery("SELECT * FROM s_campaigns_groups WHERE name='New newsletter group'")->fetchAll();

        static::assertEquals('New newsletter group', $createdNewsletterGroup[0]['name']);
    }

    public function testWriteShouldIgnoreExistingCustomerRegisteredInCustomerGroup()
    {
        $newsletterDbAdapter = $this->getNewsletterAdapter();
        /** @var Connection $dbalConnection */
        $dbalConnection = $this->getContainer()->get('dbal_connection');
        $dt = new \DateTime();
        $now = $dt->format('Y-m-d H:i:s');

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
        $groupCount = $dbalConnection->executeQuery('SELECT COUNT(*) FROM s_campaigns_groups')->fetchColumn();

        // check that no new group is created
        static::assertEquals(1, $groupCount);
    }

    private function getNewsletterAdapter(): NewsletterDbAdapter
    {
        return $this->getContainer()->get(NewsletterDbAdapter::class);
    }
}
