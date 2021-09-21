<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport\Tests\Functional\Components\SwagImportExport\DbAdapters\Articles;

use PHPUnit\Framework\TestCase;
use Shopware\Components\SwagImportExport\DbAdapters\Articles\RelationWriter;
use Shopware\Components\SwagImportExport\DbAdapters\ArticlesDbAdapter;
use SwagImportExport\Tests\Helper\DatabaseTestCaseTrait;

class RelationWriterTest extends TestCase
{
    use DatabaseTestCaseTrait;

    /**
     * @return RelationWriter
     */
    public function createRelationWriterAdapter()
    {
        //We need to get an instance of the ArticlesDbAdapter because of the given dependency
        $articlesDbAdapter = new ArticlesDbAdapter();

        return new RelationWriter($articlesDbAdapter);
    }

    public function testWriteAccessoryWithInvalidDataThrowsException()
    {
        $relationWriterAdapter = $this->createRelationWriterAdapter();

        $invalidRelationData = [
            [
                'ordernumber' => 'invalid-order-number',
                'parentIndexElement' => 0,
            ],
        ];

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Zubehör mit Bestellnummer invalid-order-number nicht gefunden.');
        $relationWriterAdapter->write('3', 'SW10003', $invalidRelationData, 'accessory', true);
    }

    public function testWriteSimilarWithInvalidDataThrowsException()
    {
        $relationWriterAdapter = $this->createRelationWriterAdapter();

        $invalidRelationData = [
            [
                'ordernumber' => 'invalid-order-number',
                'parentIndexElement' => 0,
            ],
        ];

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Ähnlicher Artikel mit Bestellnummer invalid-order-number nicht gefunden.');
        $relationWriterAdapter->write('3', 'SW10003', $invalidRelationData, 'similar', true);
    }
}
