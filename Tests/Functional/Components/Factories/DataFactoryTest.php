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
use SwagImportExport\Components\DbAdapters\CategoriesDbAdapter;
use SwagImportExport\Components\DbAdapters\CustomerDbAdapter;
use SwagImportExport\Components\DbAdapters\DataDbAdapter;
use SwagImportExport\Components\DbAdapters\MainOrdersDbAdapter;
use SwagImportExport\Components\DbAdapters\NewsletterDbAdapter;
use SwagImportExport\Components\DbAdapters\OrdersDbAdapter;
use SwagImportExport\Components\DbAdapters\ProductsDbAdapter;
use SwagImportExport\Components\DbAdapters\ProductsImagesDbAdapter;
use SwagImportExport\Components\DbAdapters\ProductsInStockDbAdapter;
use SwagImportExport\Components\DbAdapters\ProductsPricesDbAdapter;
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

    public function testCreateDbAdapterShouldCreateProductsDbAdapter(): void
    {
        $dataProvider = $this->getDataProvider();

        $productsDbAdapter = $dataProvider->createDbAdapter('articles');

        static::assertInstanceOf(ProductsDbAdapter::class, $productsDbAdapter);
        static::assertInstanceOf(DataDbAdapter::class, $productsDbAdapter);
    }

    public function testCreateDbAdapterShouldCreateProductsInstockDbAdapter(): void
    {
        $dataProvider = $this->getDataProvider();

        $productsInstockDbAdapter = $dataProvider->createDbAdapter('articlesInStock');

        static::assertInstanceOf(ProductsInStockDbAdapter::class, $productsInstockDbAdapter);
        static::assertInstanceOf(DataDbAdapter::class, $productsInstockDbAdapter);
    }

    public function testCreateDbAdapterShouldCreateProductsPricesDbAdapter(): void
    {
        $dataProvider = $this->getDataProvider();

        $productsPricesDbAdapter = $dataProvider->createDbAdapter('articlesPrices');

        static::assertInstanceOf(ProductsPricesDbAdapter::class, $productsPricesDbAdapter);
        static::assertInstanceOf(DataDbAdapter::class, $productsPricesDbAdapter);
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

    public function testCreateDbAdapterShouldCreateProductsImagesDbAdapter(): void
    {
        $dataProvider = $this->getDataProvider();

        $productsImagesDbAdapter = $dataProvider->createDbAdapter('articlesImages');

        static::assertInstanceOf(ProductsImagesDbAdapter::class, $productsImagesDbAdapter);
        static::assertInstanceOf(DataDbAdapter::class, $productsImagesDbAdapter);
    }

    private function getDataProvider(): DataProvider
    {
        return $this->getContainer()->get(DataProvider::class);
    }
}
