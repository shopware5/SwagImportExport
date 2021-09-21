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
use Shopware\Components\SwagImportExport\Validators\CustomerValidator;

class CustomerValidatorTest extends TestCase
{
    /**
     * @return CustomerValidator
     */
    public function createCustomerValidator()
    {
        return new CustomerValidator();
    }

    public function testValidatorWithEmptyRecord()
    {
        $customerValidator = $this->createCustomerValidator();

        $this->expectException(AdapterException::class);
        $this->expectExceptionMessage('User-Mail ist zwingend erforderlich.');
        $customerValidator->checkRequiredFields([]);
    }

    public function testValidatorWithoutPasswordThrowsException()
    {
        $customerValidator = new CustomerValidator();

        $this->expectException(AdapterException::class);
        $this->expectExceptionMessage('Es muss ein ungehashtes Passwort für die E-Mail  übergeben werden.');
        $customerValidator->checkRequiredFieldsForCreate([]);
    }

    public function testValidatorWithoutPasswordAndGivenUnhashedThrowsException()
    {
        $customerValidator = new CustomerValidator();

        $this->expectException(AdapterException::class);
        $this->expectExceptionMessage('Um einen neuen Benutzer mit der E-Mail validator@test.com anzulegen, muss das Passwort leer sein und unhashedPassword muss übergeben werden.');
        $customerValidator->checkRequiredFieldsForCreate(
            [
                'email' => 'validator@test.com',
                'unhashedPassword' => 'testABC',
            ]
        );
    }

    public function testValidatorWithoutCustomerGroupThrowsException()
    {
        $customerValidator = new CustomerValidator();

        $this->expectException(AdapterException::class);
        $this->expectExceptionMessage('Es muss eine Kundengruppe für den Kunden mit der E-Mail validator@test.com übergeben werden.');
        $customerValidator->checkRequiredFieldsForCreate(
            [
                'email' => 'validator@test.com',
                'unhashedPassword' => 'testABC',
                'password' => '',
                'encoder' => 'md5',
            ]
        );
    }

    public function testValidatorWithoutBillingSalutationThrowsException()
    {
        $customerValidator = new CustomerValidator();

        $this->expectException(AdapterException::class);
        $this->expectExceptionMessage('Billing salutation must be provided for user with email: validator@test.com.');
        $customerValidator->checkRequiredFieldsForCreate(
            [
                'email' => 'validator@test.com',
                'unhashedPassword' => 'testABC',
                'password' => '',
                'encoder' => 'md5',
                'customergroup' => 'EK',
            ]
        );
    }

    public function testValidatorWithoutBillingFirstNameThrowsException()
    {
        $customerValidator = new CustomerValidator();

        $this->expectException(AdapterException::class);
        $this->expectExceptionMessage('Es muss ein Rechnungsvornamen für den Kunden mit der E-Mail validator@test.com übergeben werden.');
        $customerValidator->checkRequiredFieldsForCreate(
            [
                'email' => 'validator@test.com',
                'unhashedPassword' => 'testABC',
                'password' => '',
                'encoder' => 'md5',
                'customergroup' => 'EK',
                'billingSalutation' => 'mr',
            ]
        );
    }

    public function testValidatorWithoutBillingLastNameThrowsException()
    {
        $customerValidator = new CustomerValidator();

        $this->expectException(AdapterException::class);
        $this->expectExceptionMessage('Es muss ein Rechnungsnachnamen für den Kunden mit der E-Mail validator@test.com übergeben werden.');
        $customerValidator->checkRequiredFieldsForCreate(
            [
                'email' => 'validator@test.com',
                'unhashedPassword' => 'testABC',
                'password' => '',
                'encoder' => 'md5',
                'customergroup' => 'EK',
                'billingSalutation' => 'mr',
                'billingFirstname' => 'Test',
            ]
        );
    }

    public function testValidatorWithoutBillingStreetThrowsException()
    {
        $customerValidator = new CustomerValidator();

        $this->expectException(AdapterException::class);
        $this->expectExceptionMessage('Es muss eine Rechnungsstraße für den Kunden mit der E-Mail validator@test.com übergeben werden.');
        $customerValidator->checkRequiredFieldsForCreate(
            [
                'email' => 'validator@test.com',
                'unhashedPassword' => 'testABC',
                'password' => '',
                'encoder' => 'md5',
                'customergroup' => 'EK',
                'billingSalutation' => 'mr',
                'billingFirstname' => 'Test',
                'billingLastname' => 'User',
            ]
        );
    }

    public function testValidatorWithoutBillingZipCodeThrowsException()
    {
        $customerValidator = new CustomerValidator();

        $this->expectException(AdapterException::class);
        $this->expectExceptionMessage('Es muss eine Rechnungspostleitzahl für den Kunden mit der E-Mail validator@test.com übergeben werden.');
        $customerValidator->checkRequiredFieldsForCreate(
            [
                'email' => 'validator@test.com',
                'unhashedPassword' => 'testABC',
                'password' => '',
                'encoder' => 'md5',
                'customergroup' => 'EK',
                'billingSalutation' => 'mr',
                'billingFirstname' => 'Test',
                'billingLastname' => 'User',
                'billingStreet' => 'Test street 123',
            ]
        );
    }

    public function testValidatorWithoutBillingCityThrowsException()
    {
        $customerValidator = new CustomerValidator();

        $this->expectException(AdapterException::class);
        $this->expectExceptionMessage('Es muss ein Rechnungsort für den Kunden mit der E-Mail validator@test.com übergeben werden.');
        $customerValidator->checkRequiredFieldsForCreate(
            [
                'email' => 'validator@test.com',
                'unhashedPassword' => 'testABC',
                'password' => '',
                'encoder' => 'md5',
                'customergroup' => 'EK',
                'billingSalutation' => 'mr',
                'billingFirstname' => 'Test',
                'billingLastname' => 'User',
                'billingStreet' => 'Test street 123',
                'billingZipcode' => '12345',
            ]
        );
    }

    public function testValidatorWithoutBillingCountryIdThrowsException()
    {
        $customerValidator = new CustomerValidator();

        $this->expectException(AdapterException::class);
        $this->expectExceptionMessage('Es muss eine Rechnungsland-ID für den Kunden mit der E-Mail validator@test.com übergeben werden.');
        $customerValidator->checkRequiredFieldsForCreate(
            [
                'email' => 'validator@test.com',
                'unhashedPassword' => 'testABC',
                'password' => '',
                'encoder' => 'md5',
                'customergroup' => 'EK',
                'billingSalutation' => 'mr',
                'billingFirstname' => 'Test',
                'billingLastname' => 'User',
                'billingStreet' => 'Test street 123',
                'billingZipcode' => '12345',
                'billingCity' => 'Test City',
            ]
        );
    }
}
