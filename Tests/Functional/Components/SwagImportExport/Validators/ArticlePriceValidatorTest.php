<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Functional\Components\SwagImportExport\Validators;

use PHPUnit\Framework\TestCase;
use Shopware\Components\SwagImportExport\Validators\Articles\PriceValidator;

class ArticlePriceValidatorTest extends TestCase
{
    public function test_write_with_empty_price()
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

    /**
     * @return PriceValidator
     */
    private function createArticlePriceValidator()
    {
        return new PriceValidator();
    }
}
