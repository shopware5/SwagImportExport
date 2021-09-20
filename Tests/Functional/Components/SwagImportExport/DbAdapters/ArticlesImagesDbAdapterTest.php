<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport\Tests\Functional\Components\SwagImportExport\DbAdapters;

use Doctrine\DBAL\Connection;
use PHPUnit\Framework\TestCase;
use Shopware\Components\SwagImportExport\DbAdapters\ArticlesImagesDbAdapter;
use SwagImportExport\Tests\Helper\DatabaseTestCaseTrait;

class ArticlesImagesDbAdapterTest extends TestCase
{
    use DatabaseTestCaseTrait;

    public function testWriteShouldThrowExceptionIfRecordsAreEmpty()
    {
        $articlesImagesDbAdapter = $this->createArticleImagesDbAdapter();

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Es wurden keine neuen Artikelbilder gefunden.');
        $articlesImagesDbAdapter->write([]);
    }

    public function testWriteShouldThrowExceptionHavingWrongPath()
    {
        $articlesImagesDbAdapter = $this->createArticleImagesDbAdapter();
        $records = [
            'default' => [
                [
                    'ordernumber' => 'SW10001',
                    'image' => '/../../../image.png',
                    'description' => 'testimport1',
                    'thumbnail' => 1,
                ],
            ],
        ];

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Nicht-unterstütztes Schema .');
        $articlesImagesDbAdapter->write($records);
    }

    public function testNewArticleImageShouldBeWrittenToDatabase()
    {
        $articlesImagesDbAdapter = $this->createArticleImagesDbAdapter();
        $records = [
            'default' => [
                [
                    'ordernumber' => 'SW10001',
                    'image' => $this->getImportImagePath(),
                    'description' => 'testimport1',
                    'thumbnail' => 1,
                ],
            ],
        ];
        $articlesImagesDbAdapter->write($records);

        /** @var Connection $dbalConnection */
        $dbalConnection = Shopware()->Container()->get('dbal_connection');
        $articleId = $dbalConnection->executeQuery("SELECT articleID FROM s_articles_details WHERE orderNumber='SW10001'")->fetch(\PDO::FETCH_COLUMN);
        $image = $dbalConnection->executeQuery("SELECT * FROM s_articles_img WHERE description = 'testimport1'")->fetch(\PDO::FETCH_ASSOC);

        static::assertEquals($records['default'][0]['description'], $image['description']);
        static::assertEquals($articleId, $image['articleID']);
        static::assertEquals('png', $image['extension']);
    }

    public function testWriteWithInvalidOrderNumberThrowsException()
    {
        $articlesImagesDbAdapter = $this->createArticleImagesDbAdapter();
        $records = [
            'default' => [
                [
                    'ordernumber' => 'invalid-order-number',
                    'image' => $this->getImportImagePath(),
                    'description' => 'testimport1',
                ],
            ],
        ];

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Artikel mit Nummer invalid-order-number existiert nicht.');
        $articlesImagesDbAdapter->write($records);
    }

    public function testWriteWithNotExistingImageThrowsException()
    {
        $articlesImagesDbAdapter = $this->createArticleImagesDbAdapter();
        $records = [
            'default' => [
                [
                    'ordernumber' => 'SW10001',
                    'image' => $this->getInvalidImportImagePath(),
                    'description' => 'testimport1',
                ],
            ],
        ];

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Kann file://' . \realpath(__DIR__) . '/../../../../Helper/ImportFiles/invalid_image_name.png nicht zum Lesen öffnen');
        $articlesImagesDbAdapter->write($records);
    }

    /**
     * @return ArticlesImagesDbAdapter
     */
    private function createArticleImagesDbAdapter()
    {
        return new ArticlesImagesDbAdapter();
    }

    /**
     * @return string
     */
    private function getImportImagePath()
    {
        return 'file://' . \realpath(__DIR__) . '/../../../../Helper/ImportFiles/sw-icon_blue128.png';
    }

    /**
     * @return string
     */
    private function getInvalidImportImagePath()
    {
        return 'file://' . \realpath(__DIR__) . '/../../../../Helper/ImportFiles/invalid_image_name.png';
    }
}
