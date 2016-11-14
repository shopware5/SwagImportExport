<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Functional\Components\SwagImportExport\Validators;

use Shopware\Components\SwagImportExport\Exception\AdapterException;
use Shopware\Components\SwagImportExport\Validators\Articles\ArticleValidator;

class ArticleValidatorTest extends \PHPUnit_Framework_TestCase
{
    public function test_validate_article_without_name_should_throw_exception()
    {
        $articleValidator = $this->createArticleValidator();
        $record = [
            'mainNumber' => 'SW-99999',
            'supplierId' => 2,
            'supplierName' => 'Feinbrennerei Sasse',
            'taxId' => 1
        ];

        $this->expectException(AdapterException::class);
        $this->expectExceptionMessage('Bitte geben Sie einen Artikelnamen für SW-99999 an.');
        $articleValidator->checkRequiredFieldsForCreate($record);
    }

    public function test_validate_article_without_tax_should_throw_exception()
    {
        $articleValidator = $this->createArticleValidator();
        $record = [
            'name' => 'Testartikel',
            'mainNumber' => 'SW-99999',
            'supplierId' => 2,
            'supplierName' => 'Feinbrennerei Sasse'
        ];

        $this->expectException(AdapterException::class);
        $this->expectExceptionMessage('Steuersatz für Artikel SW-99999 nicht angegeben.');
        $articleValidator->checkRequiredFieldsForCreate($record);
    }

    public function test_validate_article_should_not_throw_exception()
    {
        $articleValidator = $this->createArticleValidator();
        $record = [
            'name' => 'Testartikel',
            'mainNumber' => 'SW-99999',
            'supplierId' => 2,
            'supplierName' => 'Feinbrennerei Sasse',
            'taxId' => 1
        ];

        $this->assertNull($articleValidator->checkRequiredFieldsForCreate($record));
    }

    private function createArticleValidator()
    {
        return new ArticleValidator();
    }
}