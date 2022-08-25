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
        $addressDbAdapter = $this->getDataProvider()->createDbAdapter(DataDbAdapter::ADDRESS_ADAPTER);

        static::assertInstanceOf(AddressDbAdapter::class, $addressDbAdapter);
    }

    public function testCreateDbAdapterShouldCreateCategoriesDbAdapter(): void
    {
        $categoriesDbAdapter = $this->getDataProvider()->createDbAdapter(DataDbAdapter::CATEGORIES_ADAPTER);

        static::assertInstanceOf(CategoriesDbAdapter::class, $categoriesDbAdapter);
    }

    public function testCreateDbAdapterShouldCreateProductsDbAdapter(): void
    {
        $productsDbAdapter = $this->getDataProvider()->createDbAdapter(DataDbAdapter::PRODUCT_ADAPTER);

        static::assertInstanceOf(ProductsDbAdapter::class, $productsDbAdapter);
    }

    public function testCreateDbAdapterShouldCreateProductsInStockDbAdapter(): void
    {
        $productsInStockDbAdapter = $this->getDataProvider()->createDbAdapter(DataDbAdapter::PRODUCT_INSTOCK_ADAPTER);

        static::assertInstanceOf(ProductsInStockDbAdapter::class, $productsInStockDbAdapter);
    }

    public function testCreateDbAdapterShouldCreateProductsPricesDbAdapter(): void
    {
        $productsPricesDbAdapter = $this->getDataProvider()->createDbAdapter(DataDbAdapter::PRODUCT_PRICE_ADAPTER);

        static::assertInstanceOf(ProductsPricesDbAdapter::class, $productsPricesDbAdapter);
    }

    public function testCreateDbAdapterShouldCreateOrdersDbAdapter(): void
    {
        $ordersDbAdapter = $this->getDataProvider()->createDbAdapter(DataDbAdapter::ORDER_ADAPTER);

        static::assertInstanceOf(OrdersDbAdapter::class, $ordersDbAdapter);
    }

    public function testCreateDbAdapterShouldCreateMainOrderDbAdapter(): void
    {
        $mainOrdersDbAdapter = $this->getDataProvider()->createDbAdapter(DataDbAdapter::MAIN_ORDER_ADAPTER);

        static::assertInstanceOf(MainOrdersDbAdapter::class, $mainOrdersDbAdapter);
    }

    public function testCreateDbAdapterShouldCreateCustomerDbAdapter(): void
    {
        $customerDbAdapter = $this->getDataProvider()->createDbAdapter(DataDbAdapter::CUSTOMER_ADAPTER);

        static::assertInstanceOf(CustomerDbAdapter::class, $customerDbAdapter);
    }

    public function testCreateDbAdapterShouldCreateNewsletterDbAdapter(): void
    {
        $newsletterDbAdapter = $this->getDataProvider()->createDbAdapter(DataDbAdapter::NEWSLETTER_RECIPIENTS_ADAPTER);

        static::assertInstanceOf(NewsletterDbAdapter::class, $newsletterDbAdapter);
    }

    public function testCreateDbAdapterShouldCreateTranslationsDbAdapter(): void
    {
        $translationsDbAdapter = $this->getDataProvider()->createDbAdapter(DataDbAdapter::TRANSLATION_ADAPTER);

        static::assertInstanceOf(TranslationsDbAdapter::class, $translationsDbAdapter);
    }

    public function testCreateDbAdapterShouldCreateProductsImagesDbAdapter(): void
    {
        $productsImagesDbAdapter = $this->getDataProvider()->createDbAdapter(DataDbAdapter::PRODUCT_IMAGE_ADAPTER);

        static::assertInstanceOf(ProductsImagesDbAdapter::class, $productsImagesDbAdapter);
    }

    private function getDataProvider(): DataProvider
    {
        return $this->getContainer()->get(DataProvider::class);
    }
}
