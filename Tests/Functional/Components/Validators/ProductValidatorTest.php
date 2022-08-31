<?php
declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport\Tests\Functional\Components\Validators;

use PHPUnit\Framework\TestCase;
use SwagImportExport\Components\Exception\AdapterException;
use SwagImportExport\Components\Validators\Products\ProductValidator;

class ProductValidatorTest extends TestCase
{
    public function testValidateProductWithoutNameShouldThrowException(): void
    {
        $productValidator = $this->createProductValidator();
        $record = [
            'mainNumber' => 'SW-99999',
            'supplierId' => 2,
            'supplierName' => 'Feinbrennerei Sasse',
            'taxId' => 1,
        ];

        $this->expectException(AdapterException::class);
        $this->expectExceptionMessage('Bitte geben Sie einen Artikelnamen für SW-99999 an.');
        $productValidator->checkRequiredFieldsForCreate($record);
    }

    public function testValidateProductWithoutTaxShouldThrowException(): void
    {
        $productValidator = $this->createProductValidator();
        $record = [
            'name' => 'Testartikel',
            'mainNumber' => 'SW-99999',
            'supplierId' => 2,
            'supplierName' => 'Feinbrennerei Sasse',
        ];

        $this->expectException(AdapterException::class);
        $this->expectExceptionMessage('Steuersatz für Artikel SW-99999 nicht angegeben.');
        $productValidator->checkRequiredFieldsForCreate($record);
    }

    public function testValidateProductWithoutOrdernumberThrowsException(): void
    {
        $productValidator = $this->createProductValidator();
        $record = [
            'orderNumber' => '',
            'mainNumber' => '',
        ];

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Bestellnummer erforderlich.');
        $productValidator->checkRequiredFields($record);
    }

    public function testValidateProductWithoutMainnumberThrowsException(): void
    {
        $productValidator = $this->createProductValidator();
        $record = [
            'orderNumber' => 'ordernumber1',
            'mainNumber' => '',
        ];

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Hauptbestellnummer für Artikel ordernumber1 erforderlich.');
        $productValidator->checkRequiredFields($record);
    }

    private function createProductValidator(): ProductValidator
    {
        return new ProductValidator();
    }
}
