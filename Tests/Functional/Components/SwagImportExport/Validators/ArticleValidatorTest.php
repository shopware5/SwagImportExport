<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Functional\Components\SwagImportExport\Validators;

use PHPUnit\Framework\TestCase;
use Shopware\Components\SwagImportExport\Exception\AdapterException;
use Shopware\Components\SwagImportExport\Validators\Articles\ArticleValidator;

class ArticleValidatorTest extends TestCase
{
    public function testValidateArticleWithoutNameShouldThrowException()
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

    public function testValidateArticleWithoutTaxShouldThrowException()
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

    public function testValidateArticleWithoutOrdernumberThrowsException()
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

    public function testValidateArticleWithoutMainnumberThrowsException()
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

    /**
     * @return ArticleValidator
     */
    private function createArticleValidator()
    {
        return new ArticleValidator();
    }
}
