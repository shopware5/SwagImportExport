<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Shopware\Components\SwagImportExport\Functional\Components\SwagImportExport\Factories;

use Shopware\Components\SwagImportExport\DbAdapters\AddressDbAdapter;
use Shopware\Components\SwagImportExport\DbAdapters\ArticlesDbAdapter;
use Shopware\Components\SwagImportExport\DbAdapters\ArticlesImagesDbAdapter;
use Shopware\Components\SwagImportExport\DbAdapters\ArticlesInStockDbAdapter;
use Shopware\Components\SwagImportExport\DbAdapters\ArticlesPricesDbAdapter;
use Shopware\Components\SwagImportExport\DbAdapters\CategoriesDbAdapter;
use Shopware\Components\SwagImportExport\DbAdapters\CustomerDbAdapter;
use Shopware\Components\SwagImportExport\DbAdapters\DataDbAdapter;
use Shopware\Components\SwagImportExport\DbAdapters\MainOrdersDbAdapter;
use Shopware\Components\SwagImportExport\DbAdapters\NewsletterDbAdapter;
use Shopware\Components\SwagImportExport\DbAdapters\OrdersDbAdapter;
use Shopware\Components\SwagImportExport\DbAdapters\TranslationsDbAdapter;
use Shopware\Components\SwagImportExport\Factories\DataFactory;

class DataFactoryTest extends \PHPUnit_Framework_TestCase
{
    private function createDataFactory()
    {
        return new TestableDataFactory();
    }

    public function test_createDbAdapter_should_create_AddressDbAdapter()
    {
        $dataFactory = $this->createDataFactory();

        $addressDbAdapter = $dataFactory->createDbAdapter('addresses');

        $this->assertInstanceOf(AddressDbAdapter::class, $addressDbAdapter);
        $this->assertInstanceOf(DataDbAdapter::class, $addressDbAdapter);
    }

    public function test_createDbAdapter_should_create_CategoriesDbAdapter()
    {
        $dataFactory = $this->createDataFactory();

        $categoriesDbAdapter = $dataFactory->createDbAdapter('categories');

        $this->assertInstanceOf(CategoriesDbAdapter::class, $categoriesDbAdapter);
        $this->assertInstanceOf(DataDbAdapter::class, $categoriesDbAdapter);
    }

    public function test_createDbAdapter_should_create_ArticlesDbAdapter()
    {
        $dataFactory = $this->createDataFactory();

        $articlesDbAdapter = $dataFactory->createDbAdapter('articles');

        $this->assertInstanceOf(ArticlesDbAdapter::class, $articlesDbAdapter);
        $this->assertInstanceOf(DataDbAdapter::class, $articlesDbAdapter);
    }

    public function test_createDbAdapter_should_create_ArticlesInstockDbAdapter()
    {
        $dataFactory = $this->createDataFactory();

        $articlesInstockDbAdapter = $dataFactory->createDbAdapter('articlesInStock');

        $this->assertInstanceOf(ArticlesInStockDbAdapter::class, $articlesInstockDbAdapter);
        $this->assertInstanceOf(DataDbAdapter::class, $articlesInstockDbAdapter);
    }

    public function test_createDbAdapter_should_create_ArticlesPricesDbAdapter()
    {
        $dataFactory = $this->createDataFactory();

        $articlesPricesDbAdapter = $dataFactory->createDbAdapter('articlesPrices');

        $this->assertInstanceOf(ArticlesPricesDbAdapter::class, $articlesPricesDbAdapter);
        $this->assertInstanceOf(DataDbAdapter::class, $articlesPricesDbAdapter);
    }

    public function test_createDbAdapter_should_create_OrdersDbAdapter()
    {
        $dataFactory = $this->createDataFactory();

        $ordersDbAdapter = $dataFactory->createDbAdapter('orders');

        $this->assertInstanceOf(OrdersDbAdapter::class, $ordersDbAdapter);
        $this->assertInstanceOf(DataDbAdapter::class, $ordersDbAdapter);
    }

    public function test_createDbAdapter_should_create_MainOrderDbAdapter()
    {
        $dataFactory = $this->createDataFactory();

        $mainOrdersDbAdapter = $dataFactory->createDbAdapter('mainOrders');

        $this->assertInstanceOf(MainOrdersDbAdapter::class, $mainOrdersDbAdapter);
        $this->assertInstanceOf(DataDbAdapter::class, $mainOrdersDbAdapter);
    }

    public function test_createDbAdapter_should_create_CustomerDbAdapter()
    {
        $dataFactory = $this->createDataFactory();

        $customerDbAdapter = $dataFactory->createDbAdapter('customers');

        $this->assertInstanceOf(CustomerDbAdapter::class, $customerDbAdapter);
        $this->assertInstanceOf(DataDbAdapter::class, $customerDbAdapter);
    }

    public function test_createDbAdapter_should_create_NewsletterDbAdapter()
    {
        $dataFactory = $this->createDataFactory();

        $newsletterDbAdapter = $dataFactory->createDbAdapter('newsletter');

        $this->assertInstanceOf(NewsletterDbAdapter::class, $newsletterDbAdapter);
        $this->assertInstanceOf(DataDbAdapter::class, $newsletterDbAdapter);
    }

    public function test_createDbAdapter_should_create_TranslationsDbAdapter()
    {
        $dataFactory = $this->createDataFactory();

        $translationsDbAdapter = $dataFactory->createDbAdapter('translations');

        $this->assertInstanceOf(TranslationsDbAdapter::class, $translationsDbAdapter);
        $this->assertInstanceOf(DataDbAdapter::class, $translationsDbAdapter);
    }

    public function test_createDbAdapter_should_create_ArticlesImagesDbAdapter()
    {
        $dataFactory = $this->createDataFactory();

        $articlesImagesDbAdapter = $dataFactory->createDbAdapter('articlesImages');

        $this->assertInstanceOf(ArticlesImagesDbAdapter::class, $articlesImagesDbAdapter);
        $this->assertInstanceOf(DataDbAdapter::class, $articlesImagesDbAdapter);
    }
}

class TestableDataFactory extends DataFactory
{
    public function __construct()
    {
    }
}
