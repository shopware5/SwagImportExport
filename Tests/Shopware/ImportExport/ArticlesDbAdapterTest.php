<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\Shopware\ImportExport;

use Doctrine\DBAL\Connection;
use Shopware\Components\SwagImportExport\DbAdapters\ArticlesDbAdapter;
use Shopware\Components\SwagImportExport\DbAdapters\DataDbAdapter;
use Tests\Helper\DbAdapterTestHelper;

class ArticlesDbAdapterTest extends DbAdapterTestHelper
{
    protected $yamlFile = 'TestCases/articleDbAdapter.yml';

    public function setUp(): void
    {
        parent::setUp();

        $this->dbAdapter = 'articles';
        $this->dbTable = 's_articles';
    }

    /**
     * @param array $columns
     * @param int[] $ids
     * @param array $expected
     *
     * @dataProvider readProvider
     */
    public function testRead($columns, $ids, $expected)
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
     * @return array
     */
    public function readProvider()
    {
        return $this->getDataProvider('testRead');
    }

    /**
     * @param array $data
     *
     * @dataProvider writeProvider
     */
    public function testWrite($data)
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

    /**
     * @return array
     */
    public function writeProvider()
    {
        return $this->getDataProvider('testWrite');
    }

    /**
     * @return DataDbAdapter
     */
    private function createArticlesDbAdapter()
    {
        $dataFactory = $this->Plugin()->getDataFactory();

        return $dataFactory->createDbAdapter($this->dbAdapter);
    }

    /**
     * @param string $number
     *
     * @return array
     */
    private function getProductDataResult($number)
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

    /**
     * @param string $number
     *
     * @return array
     */
    private function getProductPriceResult($number)
    {
        $builder = $this->getQueryBuilder();

        $builder->select('prices.pricegroup', 'prices.price');
        $builder->from('s_articles_prices', 'prices');
        $builder->leftJoin('prices', 's_articles_details', 'details', 'details.id = prices.articledetailsID');
        $builder->where('details.ordernumber = :number');
        $builder->setParameter('number', $number);

        return $builder->execute()->fetchAll();
    }

    /**
     * @return \Doctrine\DBAL\Query\QueryBuilder
     */
    private function getQueryBuilder()
    {
        /** @var Connection $connection */
        $connection = Shopware()->Container()->get('dbal_connection');

        return $connection->createQueryBuilder();
    }
}
