<?php
declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport\Tests\Functional\Components\Factories;

use PHPUnit\Framework\TestCase;
use SwagImportExport\Components\DbAdapters\AddressDbAdapter;
use SwagImportExport\Components\DbAdapters\CategoriesDbAdapter;
use SwagImportExport\Components\DbAdapters\CustomerDbAdapter;
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
        $addressDbAdapter = $this->getDataProvider()->createDbAdapter('addresses');

        static::assertInstanceOf(AddressDbAdapter::class, $addressDbAdapter);
    }

    public function testCreateDbAdapterShouldCreateCategoriesDbAdapter(): void
    {
        $categoriesDbAdapter = $this->getDataProvider()->createDbAdapter('categories');

        static::assertInstanceOf(CategoriesDbAdapter::class, $categoriesDbAdapter);
    }

    public function testCreateDbAdapterShouldCreateProductsDbAdapter(): void
    {
        $productsDbAdapter = $this->getDataProvider()->createDbAdapter('articles');

        static::assertInstanceOf(ProductsDbAdapter::class, $productsDbAdapter);
    }

    public function testCreateDbAdapterShouldCreateProductsInstockDbAdapter(): void
    {
        $productsInstockDbAdapter = $this->getDataProvider()->createDbAdapter('articlesInStock');

        static::assertInstanceOf(ProductsInStockDbAdapter::class, $productsInstockDbAdapter);
    }

    public function testCreateDbAdapterShouldCreateProductsPricesDbAdapter(): void
    {
        $productsPricesDbAdapter = $this->getDataProvider()->createDbAdapter('articlesPrices');

        static::assertInstanceOf(ProductsPricesDbAdapter::class, $productsPricesDbAdapter);
    }

    public function testCreateDbAdapterShouldCreateOrdersDbAdapter(): void
    {
        $ordersDbAdapter = $this->getDataProvider()->createDbAdapter('orders');

        static::assertInstanceOf(OrdersDbAdapter::class, $ordersDbAdapter);
    }

    public function testCreateDbAdapterShouldCreateMainOrderDbAdapter(): void
    {
        $mainOrdersDbAdapter = $this->getDataProvider()->createDbAdapter('mainOrders');

        static::assertInstanceOf(MainOrdersDbAdapter::class, $mainOrdersDbAdapter);
    }

    public function testCreateDbAdapterShouldCreateCustomerDbAdapter(): void
    {
        $customerDbAdapter = $this->getDataProvider()->createDbAdapter('customers');

        static::assertInstanceOf(CustomerDbAdapter::class, $customerDbAdapter);
    }

    public function testCreateDbAdapterShouldCreateNewsletterDbAdapter(): void
    {
        $newsletterDbAdapter = $this->getDataProvider()->createDbAdapter('newsletter');

        static::assertInstanceOf(NewsletterDbAdapter::class, $newsletterDbAdapter);
    }

    public function testCreateDbAdapterShouldCreateTranslationsDbAdapter(): void
    {
        $translationsDbAdapter = $this->getDataProvider()->createDbAdapter('translations');

        static::assertInstanceOf(TranslationsDbAdapter::class, $translationsDbAdapter);
    }

    public function testCreateDbAdapterShouldCreateProductsImagesDbAdapter(): void
    {
        $productsImagesDbAdapter = $this->getDataProvider()->createDbAdapter('articlesImages');

        static::assertInstanceOf(ProductsImagesDbAdapter::class, $productsImagesDbAdapter);
    }

    private function getDataProvider(): DataProvider
    {
        return $this->getContainer()->get(DataProvider::class);
    }
}
