<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport\Tests\Functional\Components\Validators;

use PHPUnit\Framework\TestCase;
use SwagImportExport\Components\Exception\AdapterException;
use SwagImportExport\Components\Validators\AddressValidator;
use SwagImportExport\Components\Validators\Validator;

class AddressValidatorTest extends TestCase
{
    public const DONT_UPDATE_ADDRESS = false;

    public function testItCanBeCreated()
    {
        $addressValidator = new AddressValidator();

        static::assertInstanceOf(AddressValidator::class, $addressValidator);
        static::assertInstanceOf(Validator::class, $addressValidator);
    }

    public function testCheckRquieredFieldsShouldThrowExceptionIfAddressIsEmpty()
    {
        $emptyAddressRecord = [];

        $addressValidator = new AddressValidator();

        $this->expectException(AdapterException::class);
        $this->expectExceptionMessage('Es konnten keine Address-Datensätze gefunden werden.');
        $addressValidator->checkRequiredFields($emptyAddressRecord, self::DONT_UPDATE_ADDRESS);
    }

    public function testCheckRequiredFieldsShouldThrowExceptionIfRequiredFieldIsEmpty()
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

    public function testCheckRequiredFieldsShouldThrowExceptionIfCustomerCouldNotBeIdentified()
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

    public function testCheckRequiredFieldsShouldThrowExceptionIfAddressHasNoEmailButCustomernumberIsGiven()
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

    public function testCheckRequiredFieldsShouldThrowExceptionIfAddressHasCustomernumberButEmailIsGiven()
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
