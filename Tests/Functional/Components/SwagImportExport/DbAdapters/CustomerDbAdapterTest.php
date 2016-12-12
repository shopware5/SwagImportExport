<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport\Tests\Functional\Components\SwagImportExport\DbAdapters;

use Shopware\Components\SwagImportExport\DbAdapters\CustomerDbAdapter;
use SwagImportExport\Tests\Helper\DatabaseTestCaseTrait;
use Doctrine\DBAL\Connection;

class CustomerDbAdapterTest extends \PHPUnit_Framework_TestCase
{
    use DatabaseTestCaseTrait;

    /**
     * @return CustomerDbAdapter
     */
    private function createCustomerDbAdapter()
    {
        return new CustomerDbAdapter();
    }

    public function test_write_without_records_throws_exception()
    {
        $customersDbAdapter = $this->createCustomerDbAdapter();

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("Es wurden keine Kunden gefunden.");
        $customersDbAdapter->write([]);
    }

    public function test_write_should_create_customer()
    {
        $customersDbAdapter = $this->createCustomerDbAdapter();
        $records = [
            'default' =>
                [
                    $this->getTestUserDataRecord()
                ]
        ];

        $customersDbAdapter->write($records);

        /** @var Connection $dbalConnection */
        $dbalConnection = Shopware()->Container()->get('dbal_connection');
        $updatedUser = $dbalConnection->executeQuery('
              SELECT * FROM s_user
              WHERE customernumber = "21999"'
        )->fetchAll();

        $this->assertEquals($records['default'][0]['active'], $updatedUser[0]['active']);
        $this->assertEquals($records['default'][0]['customerNumber'], $updatedUser[0]['customernumber']);
    }

    public function test_write_should_update_customer()
    {
        $customerDbAdapter = $this->createCustomerDbAdapter();

        $records = [
            'default' => [
                [
                    'customerNumber' => '20001',
                    'email' => 'test@example.com',
                    'password' => 'a256a310bc1e5db755fd392c524028a8',
                    'encoder' => 'md5',
                    'active' => '0'
                ]
            ]
        ];

        $customerDbAdapter->write($records);

        /** @var Connection $dbalConnection */
        $dbalConnection = Shopware()->Container()->get('dbal_connection');
        $updatedUser = $dbalConnection->executeQuery('
              SELECT * FROM s_user
              WHERE customernumber = "20001"'
        )->fetchAll();

        $this->assertEquals($records['default'][0]['active'], $updatedUser[0]['active']);
    }

    public function test_write_should_update_billing_city()
    {
        $customerDbAdapter = $this->createCustomerDbAdapter();

        $records = [
            'default' => [
                [
                    'customerNumber' => '20001',
                    'email' => 'test@example.com',
                    'password' => 'a256a310bc1e5db755fd392c524028a8',
                    'encoder' => 'md5',
                    'billingCity' => 'New Billing City',
                    'billingZipcode' => '12345',
                    'active' => '0'
                ]
            ]
        ];

        $customerDbAdapter->write($records);

        /** @var Connection $dbalConnection */
        $dbalConnection = Shopware()->Container()->get('dbal_connection');
        $updatedUser = $dbalConnection->executeQuery('
              SELECT * FROM s_user
              WHERE customernumber = "20001"'
        )->fetchAll();
        $updatedUserBillingAddress = $dbalConnection->executeQuery('
              SELECT * FROM s_user_billingaddress
              WHERE userID = "1"'
        )->fetchAll();

        $this->assertEquals($records['default'][0]['billingCity'], $updatedUserBillingAddress[0]['city']);
        $this->assertEquals($records['default'][0]['billingZipcode'], $updatedUserBillingAddress[0]['zipcode']);
    }

    public function test_write_should_update_customer_group()
    {
        $customerDbAdapter = $this->createCustomerDbAdapter();

        $records = [
            'default' => [
                [
                    'customerNumber' => '20001',
                    'email' => 'test@example.com',
                    'password' => 'a256a310bc1e5db755fd392c524028a8',
                    'encoder' => 'md5',
                    'customergroup' => 'H',
                    'active' => '0'
                ]
            ]
        ];

        $customerDbAdapter->write($records);

        /** @var Connection $dbalConnection */
        $dbalConnection = Shopware()->Container()->get('dbal_connection');
        $updatedUser = $dbalConnection->executeQuery('
              SELECT * FROM s_user
              WHERE customernumber = "20001"'
        )->fetchAll();

        $this->assertEquals($records['default'][0]['customergroup'], $updatedUser[0]['customergroup']);
    }

    public function test_write_with_customer_group_id_throws_exception()
    {
        $customerDbAdapter = $this->createCustomerDbAdapter();

        $records = [
            'default' => [
                [
                    'customerNumber' => '20001',
                    'email' => 'test@example.com',
                    'password' => 'a256a310bc1e5db755fd392c524028a8',
                    'encoder' => 'md5',
                    'customergroup' => 'INVALID_CUSTOMER_GROUP',
                    'active' => '0'
                ]
            ]
        ];

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("Kundengruppe mit Schlüssel INVALID_CUSTOMER_GROUP nicht gefunden.");
        $customerDbAdapter->write($records);
    }

    public function test_write_should_update_phone_number()
    {
        $customerDbAdapter = $this->createCustomerDbAdapter();

        $records = [
            'default' => [
                [
                    'customerNumber' => '20001',
                    'email' => 'test@example.com',
                    'password' => 'a256a310bc1e5db755fd392c524028a8',
                    'encoder' => 'md5',
                    'billingPhone' => '05555 / 1234567',
                    'active' => '0'
                ]
            ]
        ];

        $customerDbAdapter->write($records);

        /** @var Connection $dbalConnection */
        $dbalConnection = Shopware()->Container()->get('dbal_connection');
        $updatedUser = $dbalConnection->executeQuery('
              SELECT * FROM s_user
              WHERE customernumber = "20001"'
        )->fetchAll();
        $updatedUserBillingAddress = $dbalConnection->executeQuery('
              SELECT * FROM s_user_billingaddress
              WHERE userID = "1"'
        )->fetchAll();

        $this->assertEquals($records['default'][0]['billingPhone'], $updatedUserBillingAddress[0]['phone']);
    }

    public function test_write_should_update_payment_id()
    {
        $customerDbAdapter = $this->createCustomerDbAdapter();

        $records = [
            'default' => [
                [
                    'customerNumber' => '20001',
                    'email' => 'test@example.com',
                    'password' => 'a256a310bc1e5db755fd392c524028a8',
                    'encoder' => 'md5',
                    'paymentID' => '4'
                ]
            ]
        ];

        $customerDbAdapter->write($records);

        /** @var Connection $dbalConnection */
        $dbalConnection = Shopware()->Container()->get('dbal_connection');
        $updatedUser = $dbalConnection->executeQuery('
              SELECT * FROM s_user
              WHERE customernumber = "20001"'
        )->fetchAll();

        $this->assertEquals($records['default'][0]['paymentID'], $updatedUser[0]['paymentID']);
    }

    public function test_write_should_update_last_login()
    {
        $customerDbAdapter = $this->createCustomerDbAdapter();

        $records = [
            'default' => [
                [
                    'customerNumber' => '20001',
                    'email' => 'test@example.com',
                    'password' => 'a256a310bc1e5db755fd392c524028a8',
                    'encoder' => 'md5',
                    'lastLogin' => '29.11.2016 12:13:45'
                ]
            ]
        ];

        $customerDbAdapter->write($records);

        /** @var Connection $dbalConnection */
        $dbalConnection = Shopware()->Container()->get('dbal_connection');
        $updatedUser = $dbalConnection->executeQuery('
              SELECT * FROM s_user
              WHERE customernumber = "20001"'
        )->fetchAll();

        $this->assertEquals("2016-11-29 12:13:45", $updatedUser[0]['lastlogin']);
    }

    public function test_write_without_email_throws_exception()
    {
        $customerDbAdapter = $this->createCustomerDbAdapter();

        $records = [
            'default' => [
                [
                    'customerNumber' => '20001',
                    'password' => 'a256a310bc1e5db755fd392c524028a8',
                    'encoder' => 'md5',
                    'active' => '0'
                ]
            ]
        ];

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("User-Mail ist zwingend erforderlich.");
        $customerDbAdapter->write($records);
    }

    public function test_write_without_password_throws_exception()
    {
        $customerDbAdapter = $this->createCustomerDbAdapter();

        $records = [
            'default' => [
                [
                    'customerNumber' => '21999',
                    'email' => 'test@userCompany.com',
                    'active' => '0'
                ]
            ]
        ];

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("Es muss ein ungehashtes Passwort für die E-Mail test@userCompany.com übergeben werden.");
        $customerDbAdapter->write($records);
    }

    public function test_write_create_customer_with_existing_email_throws_exception()
    {
        $customerDbAdapter = $this->createCustomerDbAdapter();

        $records = [
            'default' => [
                [
                    'customerNumber' => '99999',
                    'email' => 'test@example.com',
                    'subshopID' => '1'
                ]
            ]
        ];

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("Es sind bereits E-Mail Adressen mit test@example.com vorhanden. Bitte geben Sie auch die SubshopID an.");
        $customerDbAdapter->write($records);
    }

    public function test_write_with_invalid_sub_shop_throws_exception()
    {
        $customerDbAdapter = $this->createCustomerDbAdapter();

        $records = [
            'default' => [
                $this->getTestUserDataRecord()
            ]
        ];

        $records['default'][0]['subshopID'] = '999999';
        $records['default'][0]['customerNumber'] = '22000';
        $records['default'][0]['email'] = 'subShopTest@example.com';

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("Shop mit der ID 999999 nicht gefunden");
        $customerDbAdapter->write($records);
    }

    public function test_write_with_invalid_language_id_throws_exception()
    {
        $customerDbAdapter = $this->createCustomerDbAdapter();

        $records = [
            'default' => [
                $this->getTestUserDataRecord()
            ]
        ];

        $records['default'][0]['language'] = '999999';
        $records['default'][0]['customerNumber'] = '22001';
        $records['default'][0]['email'] = 'langShopTest@example.com';

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("Sprachshop mit der ID 999999 nicht gefunden");
        $customerDbAdapter->write($records);
    }

    public function test_write_should_update_email_address()
    {
        $customerDbAdapter = $this->createCustomerDbAdapter();

        $records = [
            'default' => [
                [
                    'customerNumber' => '20001',
                    'email' => 'test@exampleNew.com',
                    'password' => 'a256a310bc1e5db755fd392c524028a8',
                    'encoder' => 'md5'
                ]
            ]
        ];

        $customerDbAdapter->write($records);

        /** @var Connection $dbalConnection */
        $dbalConnection = Shopware()->Container()->get('dbal_connection');
        $updatedUser = $dbalConnection->executeQuery('
              SELECT * FROM s_user
              WHERE customernumber = "20001"'
        )->fetchAll();

        $this->assertEquals("test@exampleNew.com", $updatedUser[0]['email']);
    }

    public function test_write_should_update_customer_password()
    {
        $customerDbAdapter = $this->createCustomerDbAdapter();

        // password is the md5 hash for "new password"
        $records = [
            'default' => [
                [
                    'customerNumber' => '20001',
                    'email' => 'test@example.com',
                    'password' => 'ac1ef17c2db40995e9fdd40b04a5a649',
                    'encoder' => 'md5'
                ]
            ]
        ];

        $customerDbAdapter->write($records);

        /** @var Connection $dbalConnection */
        $dbalConnection = Shopware()->Container()->get('dbal_connection');
        $updatedUser = $dbalConnection->executeQuery('
              SELECT * FROM s_user
              WHERE customernumber = "20001"'
        )->fetchAll();

        $this->assertEquals("ac1ef17c2db40995e9fdd40b04a5a649", $updatedUser[0]['password']);
    }

    public function test_write_new_customer_without_billing_data_throws_exception()
    {
        $customerDbAdapter = $this->createCustomerDbAdapter();

        $records = [
            'default' => [
                [
                    'customerNumber' => '77777',
                    'email' => 'test@userCompany.com',
                    'unhashedPassword' => "TestPassword123!",
                    'encoder' => "md5",
                    'active' => '1',
                    'customergroup' => 'EK',
                    'language' => '1',
                    'subshopID' => '1'
                ]
            ]
        ];

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("Billing salutation must be provided for user with email: test@userCompany.com.");
        $customerDbAdapter->write($records);
    }

    /**
     * @return array
     */
    private function getTestUserDataRecord()
    {
        return [
            'customerNumber' => '21999',
            'email' => 'test@userCompany.com',
            'unhashedPassword' => "TestPassword123!",
            'encoder' => "md5",
            'active' => '1',
            'billingCompany' => 'Test Company',
            'billingDepartment' => '',
            'billingSalutation' => 'mr',
            'billingFirstname' => 'Test',
            'billingLastname' => 'user',
            'billingStreet' => 'Test street 55',
            'billingZipcode' => '12345',
            'billingCity' => 'Test Billing City',
            'billingPhone' => '05555 / 555555',
            'billingFax' => '',
            'billingCountryID' => '2',
            'billingStateID' => '3',
            'ustid' => '',
            'shippingCompany' => 'Test Company',
            'shippingDepartment' => '',
            'shippingSalutation' => 'mr',
            'shippingFirstname' => 'Test',
            'shippingLastname' => 'user',
            'shippingStreet' => 'Test street 56',
            'shippingZipcode' => '12345',
            'shippingCity' => 'Test Shipping City',
            'shippingCountryID' => '2',
            'paymentID' => '5',
            'newsletter' => '0',
            'accountMode' => '0',
            'customergroup' => 'EK',
            'language' => '1',
            'subshopID' => '1'
        ];
    }
}