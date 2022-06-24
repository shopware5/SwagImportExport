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
use SwagImportExport\Components\Validators\Products\PriceValidator;

class ProductPriceValidatorTest extends TestCase
{
    public function testWriteWithEmptyPrice(): void
    {
        $priceWriterDbAdapter = $this->createProductPriceValidator();

        $invalidProductPrice = [
            'price' => '',
            'priceGroup' => 'EK',
        ];
        $productOderNumber = 'SW10003';

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Preis für Artikel mit Bestellnummer SW10003 nicht gültig');
        $priceWriterDbAdapter->checkRequiredFields($invalidProductPrice, $productOderNumber);
    }

    private function createProductPriceValidator(): PriceValidator
    {
        return new PriceValidator();
    }
}
