<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\Shopware\ImportExport;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\QueryBuilder;
use Shopware\Components\SwagImportExport\DbAdapters\ArticlesDbAdapter;
use Shopware\Components\SwagImportExport\DbAdapters\DataDbAdapter;
use Shopware\Components\SwagImportExport\Factories\DataFactory;
use SwagImportExport\Tests\Helper\DatabaseTestCaseTrait;
use SwagImportExport\Tests\Helper\DbAdapterTestHelper;
use SwagImportExport\Tests\Helper\FixturesImportTrait;

class ArticlesDbAdapterTest extends DbAdapterTestHelper
{
    use DatabaseTestCaseTrait;
    use FixturesImportTrait;

    private const PRODUCT_VARIANTS_IDS = [123, 124, 14, 257, 15, 258, 16, 259, 253, 254, 255, 250, 251];

    /**
     * @var string
     */
    protected $yamlFile = 'TestCases/articleDbAdapter.yml';

    public function setUp(): void
    {
        parent::setUp();

        $this->dbAdapter = 'articles';
        $this->dbTable = 's_articles';
    }

    /**
     * @param int[] $ids
     *
     * @dataProvider readProvider
     */
    public function testRead(array $columns, array $ids, array $expected): void
    {
        $dbAdapter = $this->createArticlesDbAdapter();

        $rawData = $dbAdapter->read($ids, $columns);

        foreach ($rawData['article'] as &$item) {
            unset($item['articleId']);
        }
        unset($item);

        static::assertContains($rawData['article'][0], $expected['article']);
        static::assertContains($rawData['article'][1], $expected['article']);
        static::assertCount(\count($rawData['article']), $expected['article']);
    }

    /**
     * @param array $data
     *
     * @dataProvider writeProvider
     */
    public function testWrite($data): void
    {
        $expectedOrderNumber = 'test9999';

        /** @var ArticlesDbAdapter $dbAdapter */
        $dbAdapter = $this->createArticlesDbAdapter();
        $dbAdapter->write($data);

        $product = $this->getProductDataResult($expectedOrderNumber);
        $prices = $this->getProductPriceResult($expectedOrderNumber);

        static::assertEquals('test9999', $product[0]['ordernumber']);
        static::assertEquals('shopware-test1', $product[0]['name']);

        static::assertEquals('EK', $prices[0]['pricegroup']);
        static::assertEquals(84.033613445378, $prices[0]['price']);

        static::assertEquals('H', $prices[1]['pricegroup']);
        static::assertEquals(50, $prices[1]['price']);
    }

    public function testReadVariantIdsOfProdcutStream(): void
    {
        $this->addProductStream();

        $filter = [
            ArticlesDbAdapter::VARIANTS_FILTER_KEY => true,
            ArticlesDbAdapter::PRODUCT_STREAM_ID_FILTER_KEY => [
                0 => 999999,
            ],
        ];

        $articleDbAdapter = $this->createArticlesDbAdapter();
        $recordIds = $articleDbAdapter->readRecordIds(0, \PHP_INT_MAX, $filter);

        static::assertCount(13, array_intersect(self::PRODUCT_VARIANTS_IDS, $recordIds));
    }

    public function readProvider(): array
    {
        return $this->getDataProvider('testRead');
    }

    public function writeProvider(): array
    {
        return $this->getDataProvider('testWrite');
    }

    /**
     * @return DataDbAdapter
     */
    private function createArticlesDbAdapter()
    {
        $dataFactory = $this->getContainer()->get(DataFactory::class);

        return $dataFactory->createDbAdapter($this->dbAdapter);
    }

    private function getProductDataResult(string $number): array
    {
        $builder = $this->getQueryBuilder();

        $builder->select(['details.ordernumber', 'articles.name', 'prices.price']);
        $builder->from('s_articles', 'articles');
        $builder->leftJoin('articles', 's_articles_details', 'details', 'details.articleID = articles.id');
        $builder->leftJoin('details', 's_articles_prices', 'prices', 'prices.articledetailsID = details.id');
        $builder->where('details.ordernumber = :number');
        $builder->setParameter('number', $number);

        return $builder->execute()->fetchAll();
    }

    private function getProductPriceResult(string $number): array
    {
        $builder = $this->getQueryBuilder();

        $builder->select('prices.pricegroup', 'prices.price');
        $builder->from('s_articles_prices', 'prices');
        $builder->leftJoin('prices', 's_articles_details', 'details', 'details.id = prices.articledetailsID');
        $builder->where('details.ordernumber = :number');
        $builder->setParameter('number', $number);

        return $builder->execute()->fetchAll();
    }

    private function getQueryBuilder(): QueryBuilder
    {
        /** @var Connection $connection */
        $connection = $this->getContainer()->get('dbal_connection');

        return $connection->createQueryBuilder();
    }
}
