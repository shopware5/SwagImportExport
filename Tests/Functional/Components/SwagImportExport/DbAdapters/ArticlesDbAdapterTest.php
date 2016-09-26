<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Functional\Components\SwagImportExport\DbAdapters;

use Shopware\Components\Model\ModelManager;
use Shopware\Components\SwagImportExport\DbAdapters\ArticlesDbAdapter;

class ArticlesDbAdapterTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ModelManager
     */
    private $modelManager;

    /**
     * @var ArticlesDbAdapter
     */
    private $SUT;

    protected function setUp()
    {
        parent::setUp();

        /** @var ModelManager $modelManager */
        $this->modelManager = Shopware()->Container()->get('models');
        $this->modelManager->getConnection()->beginTransaction();

        $this->SUT = new ArticlesDbAdapter();
    }

    protected function tearDown()
    {
        parent::tearDown();
        $this->modelManager->getConnection()->rollBack();
    }

    public function test_read()
    {
        $ids = [ 3 ];
        $result = $this->SUT->read($ids, $this->getColumns());

        $this->assertArrayHasKey('article', $result, "Could not fetch articles.");
        $this->assertArrayHasKey('price', $result, "Could not fetch article prices.");
        $this->assertArrayHasKey('image', $result, "Could not fetch article image prices.");
        $this->assertArrayHasKey('propertyValue', $result, "Could not fetch article property values.");
        $this->assertArrayHasKey('similar', $result, "Could not fetch similar articles.");
        $this->assertArrayHasKey('accessory', $result, "Could not fetch accessory articles.");
        $this->assertArrayHasKey('category', $result, "Could not fetch categories.");
        $this->assertArrayHasKey('translation', $result, "Could not fetch article translations.");
        $this->assertArrayHasKey('configurator', $result, "Could not fetch article configurators");
    }

    public function test_read_should_throw_exception_if_ids_are_empty()
    {
        $columns = [ 'article' => 'article.id as articleId' ];
        $ids = [];

        $this->expectException(\Exception::class);
        $this->SUT->read($ids, $columns);
    }

    public function test_read_should_throw_exception_if_columns_are_empty()
    {
        $columns = [];
        $ids = [ 1, 2, 3 ];

        $this->expectException(\Exception::class);
        $this->SUT->read($ids, $columns);
    }

    /**
     * @return array
     */
    private function getColumns()
    {
        return [
            'article' => [
                "article.id as articleId"
            ],
            'price' => [
                "prices.articleDetailsId as variantId"
            ],
            'image' => [
                "images.id as id"
            ],
            'propertyValues' => [
                "article.id as articleId"
            ],
            'similar' => [
                "similar.id as similarId"
            ],
            'accessory' => [
                "accessory.id as accessoryId"
            ],
            'configurator' => [
                "variant.id as variantId"
            ],
            'category' => [
                "categories.id as categoryId"
            ],
            'translation' => [
                "article.id as articleId"
            ]
        ];
    }
}
