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
use Shopware\Components\SwagImportExport\DbAdapters\CustomerDbAdapter;
use SwagImportExport\Tests\Helper\DatabaseTestCaseTrait;

class CustomerDbAdapterTest extends TestCase
{
    use DatabaseTestCaseTrait;

    public function testWriteWithoutRecordsThrowsException()
    {
        $customersDbAdapter = $this->getCustomerDbAdapter();

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Es wurden keine Kunden gefunden.');
        $customersDbAdapter->write([]);
    }

    public function testWriteShouldCreateCustomer()
    {
        $customersDbAdapter = $this->getCustomerDbAdapter();
        $records = [
            'default' => [
                $this->getTestUserDataRecord(),
            ],
        ];

        $customersDbAdapter->write($records);

        /** @var Connection $dbalConnection */
        $dbalConnection = Shopware()->Container()->get('dbal_connection');
        $updatedUser = $dbalConnection->executeQuery(
            "
              SELECT * FROM s_user
              WHERE customernumber = '21999'"
        )->fetchAll();

        static::assertEquals($records['default'][0]['active'], $updatedUser[0]['active']);
        static::assertEquals($records['default'][0]['customerNumber'], $updatedUser[0]['customernumber']);
    }

    public function testWriteShouldUpdateCustomer()
    {
        $customerDbAdapter = $this->getCustomerDbAdapter();

        $records = [
            'default' => [
                [
                    'customerNumber' => '20001',
                    'email' => 'test@example.com',
                    'password' => 'a256a310bc1e5db755fd392c524028a8',
                    'encoder' => 'md5',
                    'active' => '0',
                ],
            ],
        ];

        $customerDbAdapter->write($records);

        /** @var Connection $dbalConnection */
        $dbalConnection = Shopware()->Container()->get('dbal_connection');
        $updatedUser = $dbalConnection->executeQuery(
            "
              SELECT * FROM s_user
              WHERE customernumber = '20001'"
        )->fetchAll();

        static::assertEquals($records['default'][0]['active'], $updatedUser[0]['active']);
    }

    public function testWriteShouldUpdateBillingCity()
    {
        $customerDbAdapter = $this->getCustomerDbAdapter();

        $records = [
            'default' => [
                [
                    'customerNumber' => '20001',
                    'email' => 'test@example.com',
                    'password' => 'a256a310bc1e5db755fd392c524028a8',
                    'encoder' => 'md5',
                    'billingCity' => 'New Billing City',
                    'billingZipcode' => '12345',
                    'active' => '0',
                ],
            ],
        ];

        $customerDbAdapter->write($records);

        /** @var Connection $dbalConnection */
        $dbalConnection = Shopware()->Container()->get('dbal_connection');
        $updatedUserBillingAddress = $dbalConnection->executeQuery(
            "
              SELECT * FROM s_user_addresses
              WHERE user_id = '1'"
        )->fetchAll();

        static::assertEquals($records['default'][0]['billingCity'], $updatedUserBillingAddress[0]['city']);
        static::assertEquals($records['default'][0]['billingZipcode'], $updatedUserBillingAddress[0]['zipcode']);
    }

    public function testWriteShouldUpdateCustomerGroup()
    {
        $customerDbAdapter = $this->getCustomerDbAdapter();

        $records = [
            'default' => [
                [
                    'customerNumber' => '20001',
                    'email' => 'test@example.com',
                    'password' => 'a256a310bc1e5db755fd392c524028a8',
                    'encoder' => 'md5',
                    'customergroup' => 'H',
                    'active' => '0',
                ],
            ],
        ];

        $customerDbAdapter->write($records);

        /** @var Connection $dbalConnection */
        $dbalConnection = Shopware()->Container()->get('dbal_connection');
        $updatedUser = $dbalConnection->executeQuery(
            "
              SELECT * FROM s_user
              WHERE customernumber = '20001'"
        )->fetchAll();

        static::assertEquals($records['default'][0]['customergroup'], $updatedUser[0]['customergroup']);
    }

    public function testWriteWithCustomerGroupIdThrowsException()
    {
        $customerDbAdapter = $this->getCustomerDbAdapter();

        $records = [
            'default' => [
                [
                    'customerNumber' => '20001',
                    'email' => 'test@example.com',
                    'password' => 'a256a310bc1e5db755fd392c524028a8',
                    'encoder' => 'md5',
                    'customergroup' => 'INVALID_CUSTOMER_GROUP',
                    'active' => '0',
                ],
            ],
        ];

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Kundengruppe mit Schlüssel INVALID_CUSTOMER_GROUP nicht gefunden.');
        $customerDbAdapter->write($records);
    }

    public function testWriteShouldUpdatePhoneNumber()
    {
        $customerDbAdapter = $this->getCustomerDbAdapter();

        $records = [
            'default' => [
                [
                    'customerNumber' => '20001',
                    'email' => 'test@example.com',
                    'password' => 'a256a310bc1e5db755fd392c524028a8',
                    'encoder' => 'md5',
                    'billingPhone' => '05555 / 1234567',
                    'active' => '0',
                ],
            ],
        ];

        $customerDbAdapter->write($records);

        /** @var Connection $dbalConnection */
        $dbalConnection = Shopware()->Container()->get('dbal_connection');
        $updatedUserBillingAddress = $dbalConnection->executeQuery(
            "
              SELECT * FROM s_user_addresses
              WHERE user_id = '1'"
        )->fetchAll();

        static::assertEquals($records['default'][0]['billingPhone'], $updatedUserBillingAddress[0]['phone']);
    }

    public function testWriteShouldUpdatePaymentId()
    {
        $customerDbAdapter = $this->getCustomerDbAdapter();

        $records = [
            'default' => [
                [
                    'customerNumber' => '20001',
                    'email' => 'test@example.com',
                    'password' => 'a256a310bc1e5db755fd392c524028a8',
                    'encoder' => 'md5',
                    'paymentID' => '4',
                ],
            ],
        ];

        $customerDbAdapter->write($records);

        /** @var Connection $dbalConnection */
        $dbalConnection = Shopware()->Container()->get('dbal_connection');
        $updatedUser = $dbalConnection->executeQuery(
            "
              SELECT * FROM s_user
              WHERE customernumber = '20001'"
        )->fetchAll();

        static::assertEquals($records['default'][0]['paymentID'], $updatedUser[0]['paymentID']);
    }

    public function testWriteShouldUpdateLastLogin()
    {
        $customerDbAdapter = $this->getCustomerDbAdapter();

        $records = [
            'default' => [
                [
                    'customerNumber' => '20001',
                    'email' => 'test@example.com',
                    'password' => 'a256a310bc1e5db755fd392c524028a8',
                    'encoder' => 'md5',
                    'lastLogin' => '29.11.2016 12:13:45',
                ],
            ],
        ];

        $customerDbAdapter->write($records);

        /** @var Connection $dbalConnection */
        $dbalConnection = Shopware()->Container()->get('dbal_connection');
        $updatedUser = $dbalConnection->executeQuery(
            "
              SELECT * FROM s_user
              WHERE customernumber = '20001'"
        )->fetchAll();

        static::assertEquals('2016-11-29 12:13:45', $updatedUser[0]['lastlogin']);
    }

    public function testWriteWithoutEmailThrowsException()
    {
        $customerDbAdapter = $this->getCustomerDbAdapter();

        $records = [
            'default' => [
                [
                    'customerNumber' => '20001',
                    'password' => 'a256a310bc1e5db755fd392c524028a8',
                    'encoder' => 'md5',
                    'active' => '0',
                ],
            ],
        ];

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('User-Mail ist zwingend erforderlich.');
        $customerDbAdapter->write($records);
    }

    public function testWriteWithoutPasswordThrowsException()
    {
        $customerDbAdapter = $this->getCustomerDbAdapter();

        $records = [
            'default' => [
                [
                    'customerNumber' => '21999',
                    'email' => 'test@userCompany.com',
                    'active' => '0',
                ],
            ],
        ];

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Es muss ein ungehashtes Passwort für die E-Mail test@userCompany.com übergeben werden.');
        $customerDbAdapter->write($records);
    }

    public function testWriteCreateCustomerWithExistingEmailThrowsException()
    {
        $customerDbAdapter = $this->getCustomerDbAdapter();

        $records = [
            'default' => [
                [
                    'customerNumber' => '99999',
                    'email' => 'test@example.com',
                    'subshopID' => '1',
                ],
            ],
        ];

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Es sind bereits E-Mail Adressen mit test@example.com und unterschiedlichen Kundennummern vorhanden. Bitte geben Sie auch die SubshopID an oder gleichen Sie die Kundennummer an.');
        $customerDbAdapter->write($records);
    }

    public function testWriteWithInvalidSubShopThrowsException()
    {
        $customerDbAdapter = $this->getCustomerDbAdapter();

        $records = [
            'default' => [
                $this->getTestUserDataRecord(),
            ],
        ];

        $records['default'][0]['subshopID'] = '999999';
        $records['default'][0]['customerNumber'] = '22000';
        $records['default'][0]['email'] = 'subShopTest@example.com';

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Shop mit der ID 999999 nicht gefunden');
        $customerDbAdapter->write($records);
    }

    public function testWriteWithInvalidLanguageIdThrowsException()
    {
        $customerDbAdapter = $this->getCustomerDbAdapter();

        $records = [
            'default' => [
                $this->getTestUserDataRecord(),
            ],
        ];

        $records['default'][0]['language'] = '999999';
        $records['default'][0]['customerNumber'] = '22001';
        $records['default'][0]['email'] = 'langShopTest@example.com';

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Sprachshop mit der ID 999999 nicht gefunden');
        $customerDbAdapter->write($records);
    }

    public function testWriteShouldUpdateEmailAddress()
    {
        $customerDbAdapter = $this->getCustomerDbAdapter();

        $records = [
            'default' => [
                [
                    'customerNumber' => '20001',
                    'email' => 'test@exampleNew.com',
                    'password' => 'a256a310bc1e5db755fd392c524028a8',
                    'encoder' => 'md5',
                ],
            ],
        ];

        $customerDbAdapter->write($records);

        /** @var Connection $dbalConnection */
        $dbalConnection = Shopware()->Container()->get('dbal_connection');
        $updatedUser = $dbalConnection->executeQuery(
            "
              SELECT * FROM s_user
              WHERE customernumber = '20001'"
        )->fetchAll();

        static::assertEquals('test@exampleNew.com', $updatedUser[0]['email']);
    }

    public function testWriteShouldUpdateCustomerPassword()
    {
        $customerDbAdapter = $this->getCustomerDbAdapter();

        // password is the md5 hash for "new password"
        $records = [
            'default' => [
                [
                    'customerNumber' => '20001',
                    'email' => 'test@example.com',
                    'password' => 'ac1ef17c2db40995e9fdd40b04a5a649',
                    'encoder' => 'md5',
                ],
            ],
        ];

        $customerDbAdapter->write($records);

        /** @var Connection $dbalConnection */
        $dbalConnection = Shopware()->Container()->get('dbal_connection');
        $updatedUser = $dbalConnection->executeQuery(
            "
              SELECT * FROM s_user
              WHERE customernumber = '20001'"
        )->fetchAll();

        static::assertEquals('ac1ef17c2db40995e9fdd40b04a5a649', $updatedUser[0]['password']);
    }

    public function testWriteNewCustomerWithoutBillingDataThrowsException()
    {
        $customerDbAdapter = $this->getCustomerDbAdapter();

        $records = [
            'default' => [
                [
                    'customerNumber' => '77777',
                    'email' => 'test@userCompany.com',
                    'unhashedPassword' => 'TestPassword123!',
                    'encoder' => 'md5',
                    'active' => '1',
                    'customergroup' => 'EK',
                    'language' => '1',
                    'subshopID' => '1',
                ],
            ],
        ];

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Billing salutation must be provided for user with email: test@userCompany.com.');
        $customerDbAdapter->write($records);
    }

    private function getCustomerDbAdapter(): CustomerDbAdapter
    {
        return Shopware()->Container()->get(CustomerDbAdapter::class);
    }

    /**
     * @return array
     */
    private function getTestUserDataRecord()
    {
        return [
            'customerNumber' => '21999',
            'email' => 'test@userCompany.com',
            'unhashedPassword' => 'TestPassword123!',
            'encoder' => 'md5',
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
            'subshopID' => '1',
        ];
    }
}
