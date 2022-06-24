<?php
declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport\Components\Functional\Components\Factories;

use PHPUnit\Framework\TestCase;
use SwagImportExport\Components\DbAdapters\AddressDbAdapter;
use SwagImportExport\Components\DbAdapters\ArticlesDbAdapter;
use SwagImportExport\Components\DbAdapters\ArticlesImagesDbAdapter;
use SwagImportExport\Components\DbAdapters\ArticlesInStockDbAdapter;
use SwagImportExport\Components\DbAdapters\ArticlesPricesDbAdapter;
use SwagImportExport\Components\DbAdapters\CategoriesDbAdapter;
use SwagImportExport\Components\DbAdapters\CustomerDbAdapter;
use SwagImportExport\Components\DbAdapters\DataDbAdapter;
use SwagImportExport\Components\DbAdapters\MainOrdersDbAdapter;
use SwagImportExport\Components\DbAdapters\NewsletterDbAdapter;
use SwagImportExport\Components\DbAdapters\OrdersDbAdapter;
use SwagImportExport\Components\DbAdapters\TranslationsDbAdapter;
use SwagImportExport\Components\Providers\DataProvider;
use SwagImportExport\Tests\Helper\ContainerTrait;

class DataProviderTest extends TestCase
{
    use ContainerTrait;

    public function testCreateDbAdapterShouldCreateAddressDbAdapter(): void
    {
        $dataProvider = $this->getDataProvider();

        $addressDbAdapter = $dataProvider->createDbAdapter('addresses');

        static::assertInstanceOf(AddressDbAdapter::class, $addressDbAdapter);
        static::assertInstanceOf(DataDbAdapter::class, $addressDbAdapter);
    }

    public function testCreateDbAdapterShouldCreateCategoriesDbAdapter(): void
    {
        $dataProvider = $this->getDataProvider();

        $categoriesDbAdapter = $dataProvider->createDbAdapter('categories');

        static::assertInstanceOf(CategoriesDbAdapter::class, $categoriesDbAdapter);
        static::assertInstanceOf(DataDbAdapter::class, $categoriesDbAdapter);
    }

    public function testCreateDbAdapterShouldCreateArticlesDbAdapter(): void
    {
        $dataProvider = $this->getDataProvider();

        $articlesDbAdapter = $dataProvider->createDbAdapter('articles');

        static::assertInstanceOf(ArticlesDbAdapter::class, $articlesDbAdapter);
        static::assertInstanceOf(DataDbAdapter::class, $articlesDbAdapter);
    }

    public function testCreateDbAdapterShouldCreateArticlesInstockDbAdapter(): void
    {
        $dataProvider = $this->getDataProvider();

        $articlesInstockDbAdapter = $dataProvider->createDbAdapter('articlesInStock');

        static::assertInstanceOf(ArticlesInStockDbAdapter::class, $articlesInstockDbAdapter);
        static::assertInstanceOf(DataDbAdapter::class, $articlesInstockDbAdapter);
    }

    public function testCreateDbAdapterShouldCreateArticlesPricesDbAdapter(): void
    {
        $dataProvider = $this->getDataProvider();

        $articlesPricesDbAdapter = $dataProvider->createDbAdapter('articlesPrices');

        static::assertInstanceOf(ArticlesPricesDbAdapter::class, $articlesPricesDbAdapter);
        static::assertInstanceOf(DataDbAdapter::class, $articlesPricesDbAdapter);
    }

    public function testCreateDbAdapterShouldCreateOrdersDbAdapter(): void
    {
        $dataProvider = $this->getDataProvider();

        $ordersDbAdapter = $dataProvider->createDbAdapter('orders');

        static::assertInstanceOf(OrdersDbAdapter::class, $ordersDbAdapter);
        static::assertInstanceOf(DataDbAdapter::class, $ordersDbAdapter);
    }

    public function testCreateDbAdapterShouldCreateMainOrderDbAdapter(): void
    {
        $dataProvider = $this->getDataProvider();

        $mainOrdersDbAdapter = $dataProvider->createDbAdapter('mainOrders');

        static::assertInstanceOf(MainOrdersDbAdapter::class, $mainOrdersDbAdapter);
        static::assertInstanceOf(DataDbAdapter::class, $mainOrdersDbAdapter);
    }

    public function testCreateDbAdapterShouldCreateCustomerDbAdapter(): void
    {
        $dataProvider = $this->getDataProvider();

        $customerDbAdapter = $dataProvider->createDbAdapter('customers');

        static::assertInstanceOf(CustomerDbAdapter::class, $customerDbAdapter);
        static::assertInstanceOf(DataDbAdapter::class, $customerDbAdapter);
    }

    public function testCreateDbAdapterShouldCreateNewsletterDbAdapter(): void
    {
        $dataProvider = $this->getDataProvider();

        $newsletterDbAdapter = $dataProvider->createDbAdapter('newsletter');

        static::assertInstanceOf(NewsletterDbAdapter::class, $newsletterDbAdapter);
        static::assertInstanceOf(DataDbAdapter::class, $newsletterDbAdapter);
    }

    public function testCreateDbAdapterShouldCreateTranslationsDbAdapter(): void
    {
        $dataProvider = $this->getDataProvider();

        $translationsDbAdapter = $dataProvider->createDbAdapter('translations');

        static::assertInstanceOf(TranslationsDbAdapter::class, $translationsDbAdapter);
        static::assertInstanceOf(DataDbAdapter::class, $translationsDbAdapter);
    }

    public function testCreateDbAdapterShouldCreateArticlesImagesDbAdapter(): void
    {
        $dataProvider = $this->getDataProvider();

        $articlesImagesDbAdapter = $dataProvider->createDbAdapter('articlesImages');

        static::assertInstanceOf(ArticlesImagesDbAdapter::class, $articlesImagesDbAdapter);
        static::assertInstanceOf(DataDbAdapter::class, $articlesImagesDbAdapter);
    }

    private function getDataProvider(): DataProvider
    {
        return $this->getContainer()->get(DataProvider::class);
    }
}
