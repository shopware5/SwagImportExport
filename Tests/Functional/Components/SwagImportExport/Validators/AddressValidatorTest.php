<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport\Tests\Functional\Components\SwagImportExport\Validators;

use PHPUnit\Framework\TestCase;
use Shopware\Components\SwagImportExport\Exception\AdapterException;
use Shopware\Components\SwagImportExport\Validators\AddressValidator;
use Shopware\Components\SwagImportExport\Validators\Validator;

class AddressValidatorTest extends TestCase
{
    const DONT_UPDATE_ADDRESS = false;

    public function test_it_can_be_created()
    {
        $addressValidator = new AddressValidator();

        static::assertInstanceOf(AddressValidator::class, $addressValidator);
        static::assertInstanceOf(Validator::class, $addressValidator);
    }

    public function test_checkRquieredFields_should_throw_exception_if_address_is_empty()
    {
        $emptyAddressRecord = [];

        $addressValidator = new AddressValidator();

        $this->expectException(AdapterException::class);
        $this->expectExceptionMessage('Es konnten keine Address-Datensätze gefunden werden.');
        $addressValidator->checkRequiredFields($emptyAddressRecord, self::DONT_UPDATE_ADDRESS);
    }

    public function test_checkRequiredFields_should_throw_exception_if_required_field_is_empty()
    {
        $emptyAddressRecord = [
            'userID' => 999,
            'firstname' => '',
            'lastname' => 'lastname',
        ];

        $addressValidator = new AddressValidator();

        $this->expectException(AdapterException::class);
        $this->expectExceptionMessage('Firstname ist ein Pflichtfeld. Datensatz: userID: 999, firstname:  - , lastname: lastname');
        $addressValidator->checkRequiredFields($emptyAddressRecord, self::DONT_UPDATE_ADDRESS);
    }

    public function test_checkRequiredFields_should_throw_exception_if_customer_could_not_be_identified()
    {
        $addressWithoutCustomerId = [
            'firstname' => 'some value',
            'lastname' => 'some value',
            'zipcode' => 'some value',
            'city' => 'some value',
            'countryID' => '1',
        ];

        $addressValidator = new AddressValidator();

        $this->expectException(AdapterException::class);
        $this->expectExceptionMessage('Kunde konnte nicht zugeordnet werden. Email und customernumber oder userID ist nötig. Datensatz: firstname: some value,');
        $addressValidator->checkRequiredFields($addressWithoutCustomerId, self::DONT_UPDATE_ADDRESS);
    }

    public function test_checkRequiredFields_should_throw_exception_if_address_has_no_email_but_customernumber_is_given()
    {
        $addressWithoutEmail = [
            'firstname' => 'some value',
            'lastname' => 'some value',
            'zipcode' => 'some value',
            'city' => 'some value',
            'countryID' => 'some value',
            'customernumber' => '1',
        ];

        $addressValidator = new AddressValidator();

        $this->expectException(AdapterException::class);
        $this->expectExceptionMessage('Kunde konnte nicht zugeordnet werden. Email und customernumber oder userID ist nötig. Datensatz: firstname: some value');
        $addressValidator->checkRequiredFields($addressWithoutEmail, self::DONT_UPDATE_ADDRESS);
    }

    public function test_checkRequiredFields_should_throw_exception_if_address_has_customernumber_but_email_is_given()
    {
        $addressWithoutCustomernumber = [
            'firstname' => 'some value',
            'lastname' => 'some value',
            'zipcode' => 'some value',
            'city' => 'some value',
            'countryID' => 'some value',
            'email' => 'test@example.org',
        ];

        $addressValidator = new AddressValidator();

        $this->expectException(AdapterException::class);
        $this->expectExceptionMessage('Kunde konnte nicht zugeordnet werden. Email und customernumber oder userID ist nötig. Datensatz: firstname: some value');
        $addressValidator->checkRequiredFields($addressWithoutCustomernumber, self::DONT_UPDATE_ADDRESS);
    }
}
