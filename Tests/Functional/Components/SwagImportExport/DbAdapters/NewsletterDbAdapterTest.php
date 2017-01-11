<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport\Tests\Functional\Components\SwagImportExport\DbAdapters;

use Doctrine\DBAL\Connection;
use Shopware\Components\SwagImportExport\DbAdapters\NewsletterDbAdapter;
use SwagImportExport\Tests\Helper\DatabaseTestCaseTrait;

class NewsletterDbAdapterTest extends \PHPUnit_Framework_TestCase
{
    use DatabaseTestCaseTrait;

    /**
     * @return NewsletterDbAdapter
     */
    private function createNewsletterAdapter()
    {
        return new NewsletterDbAdapter();
    }

    public function test_write_throws_exception_if_records_are_empty()
    {
        $newsletterDbAdapter = $this->createNewsletterAdapter();

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("Es wurden keine Newsletter gefunden.");
        $newsletterDbAdapter->write([]);
    }

    public function test_should_add_customer_as_newsletter_recipient()
    {
        $newsletterDbAdapter = $this->createNewsletterAdapter();
        $customerData = [
            'default' => [
                [
                    'email' => 'test@example.com',
                    'userID' => 1
                ]
            ]
        ];

        $newsletterDbAdapter->write($customerData);

        /** @var Connection $dbalConnection */
        $dbalConnection = Shopware()->Container()->get('dbal_connection');
        $createdRecipient = $dbalConnection->executeQuery('SELECT * FROM s_campaigns_mailaddresses WHERE email="test@example.com"')->fetchAll();

        $this->assertEquals($customerData['default'][0]['email'], $createdRecipient[0]['email']);
    }

    public function test_write_should_create_newsletter_recipient()
    {
        $newsletterDbAdapter = $this->createNewsletterAdapter();

        $notExistingRecipient = [ 'default' => [
            [
                'email' => 'email_address_which_does_not_exist@example.org'
            ]
        ]];

        $newsletterDbAdapter->write($notExistingRecipient);

        /** @var Connection $dbalConnection */
        $dbalConnection = Shopware()->Container()->get('dbal_connection');
        $createdRecipient = $dbalConnection->executeQuery('SELECT * FROM s_campaigns_mailaddresses WHERE email="email_address_which_does_not_exist@example.org"')->fetchAll();

        $this->assertEquals($notExistingRecipient['default'][0]['email'], $createdRecipient[0]['email']);
    }

    public function test_write_should_create_newsletter_recipient_contact_data()
    {
        $newsletterDBAdapter = $this->createNewsletterAdapter();

        $recipientWithContactData = [ 'default' => [
            [
                'email' => 'email_address_which_does_not_exists@example.org',
                'firstname' => 'Test',
                'lastname' => 'Recipient',
                'zipcode' => '12345'
            ]
        ]];

        $newsletterDBAdapter->write($recipientWithContactData);

        /** @var Connection $dbalConnection */
        $dbalConnection = Shopware()->Container()->get('dbal_connection');
        $createdRecipient = $dbalConnection->executeQuery('SELECT * FROM s_campaigns_maildata WHERE email="email_address_which_does_not_exists@example.org"')->fetchAll();

        $this->assertEquals($recipientWithContactData['default'][0]['firstname'], $createdRecipient[0]['firstname']);
        $this->assertEquals($recipientWithContactData['default'][0]['lastname'], $createdRecipient[0]['lastname']);
        $this->assertEquals($recipientWithContactData['default'][0]['zipcode'], '12345');
    }

    public function test_write_should_create_newsletter_group()
    {
        $newsletterDbAdapter = $this->createNewsletterAdapter();

        $recipientWithNewNewsletterGroup = [ 'default' => [
            [
                'email' => 'email_which_does_not_exists@example.org',
                'groupName' => 'New newsletter group'
            ]
        ]];

        $newsletterDbAdapter->write($recipientWithNewNewsletterGroup);

        /** @var Connection $dbalConnection */
        $dbalConnection = Shopware()->Container()->get('dbal_connection');
        $createdNewsletterGroup = $dbalConnection->executeQuery('SELECT * FROM s_campaigns_groups WHERE name="New newsletter group"')->fetchAll();

        $this->assertEquals('New newsletter group', $createdNewsletterGroup[0]['name']);
    }

    public function test_write_should_ignore_existing_customer_registered_in_customer_group()
    {
        $newsletterDbAdapter = $this->createNewsletterAdapter();
        /** @var Connection $dbalConnection */
        $dbalConnection = Shopware()->Container()->get('dbal_connection');
        $dt = new \DateTime();
        $now = $dt->format('Y-m-d H:i:s');

        // register existing demo customer to newsletter
        $dbalConnection->executeQuery("INSERT INTO s_campaigns_mailaddresses (customer, email, added) VALUES (1, 'test@example.com', ?)", [$now]);

        $record = [
            'default' => [
                [
                    'email' => 'test@example.com',
                    'groupName' => ''
                ]
            ]
        ];

        $newsletterDbAdapter->write($record);
        $groupCount = $dbalConnection->executeQuery('SELECT COUNT(*) FROM s_campaigns_groups')->fetchColumn();

        // check that no new group is created
        $this->assertEquals(1, $groupCount);
    }
}
