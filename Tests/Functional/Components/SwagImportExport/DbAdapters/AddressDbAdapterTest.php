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
use Shopware\Components\SwagImportExport\DbAdapters\AddressDbAdapter;
use Shopware\Components\SwagImportExport\DbAdapters\DataDbAdapter;
use SwagImportExport\Tests\Helper\DatabaseTestCaseTrait;

class AddressDbAdapterTest extends TestCase
{
    use DatabaseTestCaseTrait;

    const NO_START = 0;
    const NO_LIMIT = 0;
    const NO_FILTER = [];
    const COUNTRY_ID_USA = 28;
    const CUSTOMER_ID = 1;
    const EXISTING_ADDRESS = 3;
    const NOT_EXISTING_USERID = 999999;
    const STATE_ID_ALABAMA = 20;

    public function test_it_can_be_created()
    {
        $addressDbAdapter = $this->createAddressDbAdapter();

        static::assertInstanceOf(AddressDbAdapter::class, $addressDbAdapter);
        static::assertInstanceOf(DataDbAdapter::class, $addressDbAdapter);
    }

    public function test_readRecordIds_should_return_ids()
    {
        $allAddressIdsInDatabase = [1, 2, 3, 4];
        $addressDbAdapter = $this->createAddressDbAdapter();

        $addressIds = $addressDbAdapter->readRecordIds(self::NO_START, self::NO_LIMIT, self::NO_FILTER);

        static::assertEquals($allAddressIdsInDatabase, $addressIds);
    }

    public function test_readRecordIds_with_start_should_return_two_ids()
    {
        $fetchedAmountOfIds = 2;
        $firstResult = 2;
        $addressDbAdapter = $this->createAddressDbAdapter();

        $addressIds = $addressDbAdapter->readRecordIds($firstResult, self::NO_LIMIT, self::NO_FILTER);

        static::assertCount($fetchedAmountOfIds, $addressIds);
    }

    public function test_readRecordIds_with_limit_should_return_two_ids()
    {
        $fetchedAmountOfIds = 2;
        $limit = 2;
        $addressDbAdapter = $this->createAddressDbAdapter();

        $addressIds = $addressDbAdapter->readRecordIds(self::NO_START, $limit, self::NO_FILTER);

        static::assertCount($fetchedAmountOfIds, $addressIds);
    }

    public function test_read_should_return_addresses_by_ids_and_select_given_columns()
    {
        $addressIdsToFetch = [1, 2];
        $selectedColumns = ['address.company', 'address.firstname', 'address.lastname', 'customer.email'];

        $addressDbAdapter = $this->createAddressDbAdapter();
        $addresses = $addressDbAdapter->read($addressIdsToFetch, $selectedColumns);

        $addresses = $addresses['address'];
        static::assertEquals('Max', $addresses[0]['firstname']);
        static::assertEquals('Mustermann', $addresses[0]['lastname']);
        static::assertEquals('Muster GmbH', $addresses[0]['company']);
        static::assertEquals('test@example.com', $addresses[0]['email']);
        static::assertCount(2, $addresses);
    }

    public function test_read_should_return_attributes()
    {
        $connection = Shopware()->Container()->get('dbal_connection');
        $connection->executeQuery(\file_get_contents(__DIR__ . '/_fixtures/address_attribute_demo.sql'));

        $addressIdsToFetch = [1];
        $selectedColumns = ['attribute.text1', 'attribute.text2'];

        $addressDbAdapter = $this->createAddressDbAdapter();
        $addresses = $addressDbAdapter->read($addressIdsToFetch, $selectedColumns);

        $addresses = $addresses['address'];
        static::assertEquals('Attr value', $addresses[0]['text1']);
    }

    public function test_write_should_create_new_address_with_all_required_fields()
    {
        $addresses = [
            'address' => [
                [
                    'firstname' => 'My firstname',
                    'lastname' => 'My lastname',
                    'zipcode' => '12345',
                    'city' => 'My city',
                    'countryID' => self::COUNTRY_ID_USA,
                    'userID' => self::CUSTOMER_ID,
                ],
            ],
        ];

        $addressDbAdapter = $this->createAddressDbAdapter();
        $addressDbAdapter->write($addresses);

        /** @var Connection $connection */
        $connection = Shopware()->Container()->get('dbal_connection');
        $createdAddresses = $connection->executeQuery("SELECT * FROM s_user_addresses WHERE firstname='My firstname'")->fetchAll();

        $this->assertAddress($createdAddresses);
    }

    public function test_write_should_identify_customer_by_email_and_customernumber()
    {
        $addresses = [
            'address' => [
                [
                    'firstname' => 'My firstname',
                    'lastname' => 'My lastname',
                    'zipcode' => '12345',
                    'city' => 'My city',
                    'countryID' => self::COUNTRY_ID_USA,
                    'email' => 'test@example.com',
                    'customernumber' => '20001',
                ],
            ],
        ];

        $addressDbAdapter = $this->createAddressDbAdapter();
        $addressDbAdapter->write($addresses);

        /** @var Connection $connection */
        $connection = Shopware()->Container()->get('dbal_connection');
        $createdAddresses = $connection->executeQuery("SELECT * FROM s_user_addresses WHERE firstname='My firstname'")->fetchAll();

        $this->assertAddress($createdAddresses);
    }

    public function test_write_should_identify_customer_by_email_and_customernumber_if_an_invalid_id_was_given()
    {
        $addresses = [
            'address' => [
                [
                    'firstname' => 'My firstname',
                    'lastname' => 'My lastname',
                    'zipcode' => '12345',
                    'city' => 'My city',
                    'countryID' => self::COUNTRY_ID_USA,
                    'userID' => 999999,
                    'email' => 'test@example.com',
                    'customernumber' => '20001',
                ],
            ],
        ];

        $addressesDbAdapter = $this->createAddressDbAdapter();
        $addressesDbAdapter->write($addresses);

        /** @var Connection $connection */
        $connection = Shopware()->Container()->get('dbal_connection');
        $createdAddresses = $connection->executeQuery("SELECT * FROM s_user_addresses WHERE firstname='My firstname'")->fetchAll();

        $this->assertAddress($createdAddresses);
    }

    public function test_write_should_update_existing_address()
    {
        /** @var Connection $connection */
        $connection = Shopware()->Container()->get('dbal_connection');
        $demoSQL = \file_get_contents(__DIR__ . '/_fixtures/address_demo.sql');
        $connection->executeQuery($demoSQL)->execute();

        $updatedAddressId = $connection->lastInsertId();

        $addresses = [
            'address' => [
                [
                    'id' => $updatedAddressId,
                    'firstname' => 'My firstname',
                    'lastname' => 'My lastname',
                    'zipcode' => '12345',
                    'city' => 'My city',
                    'countryID' => self::COUNTRY_ID_USA,
                    'userID' => self::CUSTOMER_ID,
                ],
            ],
        ];

        $addressDbAdapter = $this->createAddressDbAdapter();
        $addressDbAdapter->write($addresses);

        $createdAddresses = $connection->executeQuery(
            'SELECT * FROM s_user_addresses WHERE id=?',
            [$updatedAddressId]
        )->fetchAll();

        $this->assertAddress($createdAddresses);
    }

    public function test_write_should_import_vatId()
    {
        $addresses = [
            'address' => [
                [
                    'firstname' => 'My firstname',
                    'lastname' => 'My lastname',
                    'zipcode' => '12345',
                    'city' => 'My city',
                    'countryID' => self::COUNTRY_ID_USA,
                    'userID' => self::CUSTOMER_ID,
                    'vatId' => 'My VatId',
                ],
            ],
        ];

        $addressDbAdapter = $this->createAddressDbAdapter();
        $addressDbAdapter->write($addresses);

        /** @var Connection $connection */
        $connection = Shopware()->Container()->get('dbal_connection');
        $createdAddresses = $connection->executeQuery("SELECT * FROM s_user_addresses WHERE firstname='My firstname'")->fetchAll();

        $this->assertAddress($createdAddresses);
        static::assertEquals('My VatId', $createdAddresses[0]['ustid']);
    }

    public function test_write_should_import_additionalAddressLines()
    {
        $addresses = [
            'address' => [
                [
                    'firstname' => 'My firstname',
                    'lastname' => 'My lastname',
                    'zipcode' => '12345',
                    'city' => 'My city',
                    'countryID' => self::COUNTRY_ID_USA,
                    'userID' => self::CUSTOMER_ID,
                    'additionalAddressLine1' => 'My additional address',
                    'additionalAddressLine2' => 'My additional address2',
                ],
            ],
        ];

        $addressDbAdapter = $this->createAddressDbAdapter();
        $addressDbAdapter->write($addresses);

        /** @var Connection $connection */
        $connection = Shopware()->Container()->get('dbal_connection');
        $createdAddresses = $connection->executeQuery("SELECT * FROM s_user_addresses WHERE firstname='My firstname'")->fetchAll();

        $this->assertAddress($createdAddresses);
        static::assertEquals('My additional address', $createdAddresses[0]['additional_address_line1']);
        static::assertEquals('My additional address2', $createdAddresses[0]['additional_address_line2']);
    }

    public function test_write_should_import_attributes()
    {
        $addresses = [
            'address' => [
                [
                    'firstname' => 'My firstname',
                    'lastname' => 'My lastname',
                    'zipcode' => '12345',
                    'city' => 'My city',
                    'countryID' => self::COUNTRY_ID_USA,
                    'userID' => self::CUSTOMER_ID,
                    'attributeText1' => 'text1',
                    'attributeText2' => 'text2',
                ],
            ],
        ];

        $addressDbAdapter = $this->createAddressDbAdapter();
        $addressDbAdapter->write($addresses);

        /** @var Connection $connection */
        $connection = Shopware()->Container()->get('dbal_connection');
        $addressId = $connection->executeQuery("SELECT id FROM s_user_addresses WHERE firstname='My firstname'")->fetchColumn();
        $createdAttribute = $connection->executeQuery("SELECT * FROM s_user_addresses_attributes WHERE address_id={$addressId}")->fetchAll();

        static::assertEquals('text1', $createdAttribute[0]['text1']);
        static::assertEquals('text2', $createdAttribute[0]['text2']);
    }

    public function test_write_should_create_address_with_given_id()
    {
        $addresses = [
            'address' => [
                [
                    'id' => 99999,
                    'firstname' => 'My firstname',
                    'lastname' => 'My lastname',
                    'zipcode' => '12345',
                    'city' => 'My city',
                    'countryID' => self::COUNTRY_ID_USA,
                    'userID' => self::CUSTOMER_ID,
                    'attributeText1' => 'text1',
                    'attributeText2' => 'text2',
                ],
            ],
        ];

        $addressDbAdapter = $this->createAddressDbAdapter();
        $addressDbAdapter->write($addresses);

        /** @var Connection $connection */
        $connection = Shopware()->Container()->get('dbal_connection');
        $addressId = $connection->executeQuery("SELECT id FROM s_user_addresses WHERE firstname='My firstname'")->fetchColumn();

        static::assertEquals(99999, $addressId);
    }

    public function test_getColumns_should_get_all_required_columns()
    {
        $addressDbAdapter = $this->createAddressDbAdapter();
        $columns = $addressDbAdapter->getColumns();

        static::assertContains('address.id as id', $columns);
        static::assertContains('address.company as company', $columns);
        static::assertContains('address.firstname as firstname', $columns);
        static::assertContains('address.lastname as lastname', $columns);
        static::assertContains('address.street as street', $columns);
        static::assertContains('address.city as city', $columns);
        static::assertContains('address.zipcode as zipcode', $columns);
        static::assertContains('address.zipcode as zipcode', $columns);

        static::assertContains('country.id as countryID', $columns);
        static::assertContains('state.id as stateID', $columns);

        static::assertContains('customer.email as email', $columns);
        static::assertContains('customer.number as customernumber', $columns);
        static::assertContains('customer.id as userID', $columns);
    }

    public function test_getColumns_should_get_attribute_columns()
    {
        $addressDbAdapter = $this->createAddressDbAdapter();
        $columns = $addressDbAdapter->getColumns();

        static::assertContains('attribute.text1 as attributeText1', $columns);
        static::assertContains('attribute.text2 as attributeText2', $columns);
    }

    public function test_write_should_throw_exception_if_no_addresses_were_given()
    {
        $addressDbAdapter = $this->createAddressDbAdapter();

        $this->expectException(\Exception::class);
        $addressDbAdapter->write([]);
    }

    public function test_write_should_throw_exception_if_customer_does_not_eixst()
    {
        $addresses = [
            'address' => [
                [
                    'userID' => self::NOT_EXISTING_USERID,
                    'firstname' => 'My firstname',
                    'lastname' => 'My lastname',
                    'zipcode' => '12345',
                    'city' => 'My city',
                    'countryID' => self::COUNTRY_ID_USA,
                ],
            ],
        ];

        $addressDbAdapter = $this->createAddressDbAdapter();

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Konnte Kunden nicht finden. Email: , Customernumber: , userID: 999999');
        $addressDbAdapter->write($addresses);
    }

    public function test_write_should_update_state()
    {
        /** @var Connection $connection */
        $connection = Shopware()->Container()->get('dbal_connection');
        $connection->executeQuery(\file_get_contents(__DIR__ . '/_fixtures/address_with_state_demo.sql'));
        $addressId = $connection->lastInsertId();

        $addresses = [
            'address' => [
                [
                    'id' => $addressId,
                    'firstname' => 'My firstname',
                    'lastname' => 'My lastname',
                    'zipcode' => '12345',
                    'city' => 'My city',
                    'countryID' => self::COUNTRY_ID_USA,
                    'stateID' => self::STATE_ID_ALABAMA,
                    'userID' => self::CUSTOMER_ID,
                ],
            ],
        ];

        $addressDbAdapter = $this->createAddressDbAdapter();
        $addressDbAdapter->write($addresses);

        /** @var Connection $connection */
        $connection = Shopware()->Container()->get('dbal_connection');
        $updateAddress = $connection->executeQuery("SELECT * FROM s_user_addresses WHERE id={$addressId}")->fetchAll();

        static::assertEquals(self::STATE_ID_ALABAMA, $updateAddress[0]['state_id']);
    }

    public function test_write_should_throw_exception_if_stateID_was_not_found()
    {
        $addresses = [
            'address' => [
                [
                    'firstname' => 'My firstname',
                    'lastname' => 'My lastname',
                    'zipcode' => '12345',
                    'city' => 'My city',
                    'countryID' => self::COUNTRY_ID_USA,
                    'stateID' => 99999,
                    'userID' => self::CUSTOMER_ID,
                ],
            ],
        ];

        $addressDbAdapter = $this->createAddressDbAdapter();

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Bundesland wurde nicht gefunden mit stateID: 99999');
        $addressDbAdapter->write($addresses);
    }

    /**
     * @return AddressDbAdapter
     */
    private function createAddressDbAdapter()
    {
        return new AddressDbAdapter();
    }

    /**
     * @param array $createdAddresses
     */
    private function assertAddress($createdAddresses)
    {
        static::assertEquals('My firstname', $createdAddresses[0]['firstname']);
        static::assertEquals('My lastname', $createdAddresses[0]['lastname']);
        static::assertEquals('12345', $createdAddresses[0]['zipcode']);
        static::assertEquals('My city', $createdAddresses[0]['city']);
        static::assertEquals(self::COUNTRY_ID_USA, $createdAddresses[0]['country_id']);
        static::assertEquals(self::CUSTOMER_ID, $createdAddresses[0]['user_id']);
    }
}
