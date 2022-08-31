<?php
declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport\Tests\Functional\Components\DbAdapters\Products;

use PHPUnit\Framework\TestCase;
use SwagImportExport\Components\DbAdapters\Products\RelationWriter;
use SwagImportExport\Components\DbAdapters\ProductsDbAdapter;
use SwagImportExport\Tests\Helper\ContainerTrait;
use SwagImportExport\Tests\Helper\DatabaseTestCaseTrait;

class RelationWriterTest extends TestCase
{
    use DatabaseTestCaseTrait;
    use ContainerTrait;

    public function getRelationWriterAdapter(): RelationWriter
    {
        // We need to get an instance of the ProductsDbAdapter because of the given dependency
        $productsDbAdapter = $this->getContainer()->get(ProductsDbAdapter::class);

        $relationWriter = $this->getContainer()->get(RelationWriter::class);
        $relationWriter->setProductsDbAdapter($productsDbAdapter);

        return $relationWriter;
    }

    public function testWriteAccessoryWithInvalidDataThrowsException(): void
    {
        $relationWriterAdapter = $this->getRelationWriterAdapter();

        $invalidRelationData = [
            [
                'ordernumber' => 'invalid-order-number',
                'parentIndexElement' => 0,
            ],
        ];

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Zubehör mit Bestellnummer invalid-order-number nicht gefunden.');
        $relationWriterAdapter->write(3, 'SW10003', $invalidRelationData, 'accessory', true);
    }

    public function testWriteSimilarWithInvalidDataThrowsException(): void
    {
        $relationWriterAdapter = $this->getRelationWriterAdapter();

        $invalidRelationData = [
            [
                'ordernumber' => 'invalid-order-number',
                'parentIndexElement' => 0,
            ],
        ];

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Ähnlicher Artikel mit Bestellnummer invalid-order-number nicht gefunden.');
        $relationWriterAdapter->write(3, 'SW10003', $invalidRelationData, 'similar', true);
    }
}
