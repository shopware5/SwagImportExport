<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport\Tests\Functional\Components\SwagImportExport\Validators;

use PHPUnit\Framework\TestCase;
use SwagImportExport\Components\Validators\Articles\PriceValidator;

class ArticlePriceValidatorTest extends TestCase
{
    public function testWriteWithEmptyPrice(): void
    {
        $priceWriterDbAdapter = $this->createArticlePriceValidator();

        $invalidArticlePrice = [
            'price' => '',
            'priceGroup' => 'EK',
        ];
        $articleOderNumber = 'SW10003';

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Preis für Artikel mit Bestellnummer SW10003 nicht gültig');
        $priceWriterDbAdapter->checkRequiredFields($invalidArticlePrice, $articleOderNumber);
    }

    private function createArticlePriceValidator(): PriceValidator
    {
        return new PriceValidator();
    }
}
