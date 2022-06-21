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
use SwagImportExport\Components\Validators\Articles\ArticleValidator;

class ArticleValidatorTest extends TestCase
{
    public function testValidateArticleWithoutNameShouldThrowException(): void
    {
        $articleValidator = $this->createArticleValidator();
        $record = [
            'mainNumber' => 'SW-99999',
            'supplierId' => 2,
            'supplierName' => 'Feinbrennerei Sasse',
            'taxId' => 1,
        ];

        $this->expectException(AdapterException::class);
        $this->expectExceptionMessage('Bitte geben Sie einen Artikelnamen für SW-99999 an.');
        $articleValidator->checkRequiredFieldsForCreate($record);
    }

    public function testValidateArticleWithoutTaxShouldThrowException(): void
    {
        $articleValidator = $this->createArticleValidator();
        $record = [
            'name' => 'Testartikel',
            'mainNumber' => 'SW-99999',
            'supplierId' => 2,
            'supplierName' => 'Feinbrennerei Sasse',
        ];

        $this->expectException(AdapterException::class);
        $this->expectExceptionMessage('Steuersatz für Artikel SW-99999 nicht angegeben.');
        $articleValidator->checkRequiredFieldsForCreate($record);
    }

    public function testValidateArticleWithoutOrdernumberThrowsException(): void
    {
        $articleValidator = $this->createArticleValidator();
        $record = [
            'orderNumber' => '',
            'mainNumber' => '',
        ];

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Bestellnummer erforderlich.');
        $articleValidator->checkRequiredFields($record);
    }

    public function testValidateArticleWithoutMainnumberThrowsException(): void
    {
        $articleValidator = $this->createArticleValidator();
        $record = [
            'orderNumber' => 'ordernumber1',
            'mainNumber' => '',
        ];

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Hauptbestellnummer für Artikel ordernumber1 erforderlich.');
        $articleValidator->checkRequiredFields($record);
    }

    private function createArticleValidator(): ArticleValidator
    {
        return new ArticleValidator();
    }
}
