<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Shopware\Models\Shop\Shop;

require __DIR__ . '/../../../../../../../autoload.php';

class ImportExportTestKernel extends \Shopware\Kernel
{
    const IMPORT_FILES_DIR = __DIR__ . '/Helper/ImportFiles/';

    /**
     * @throws RuntimeException
     */
    public static function start()
    {
        $kernel = new self(\getenv('SHOPWARE_ENV') ?: 'testing', true);
        $kernel->boot();

        Shopware()->Loader()->registerNamespace('SwagImportExport\Tests', __DIR__ . '/../Tests/');
        Shopware()->Loader()->registerNamespace('Tests\Helper', __DIR__ . '/Helper/');
        Shopware()->Loader()->registerNamespace('Tests\Shopware\ImportExport', __DIR__ . '/Shopware/ImportExport/');
        Shopware()->Loader()->registerNamespace('Shopware\Setup\SwagImportExport', __DIR__ . '/../Setup/SwagImportExport/');
        Shopware()->Loader()->registerNamespace('Shopware\Components', __DIR__ . '/../Components/');
        Shopware()->Loader()->registerNamespace('Shopware\CustomModels', __DIR__ . '/../Models/');

        $container = $kernel->getContainer();
        $container->get('plugins')->Core()->ErrorHandler()->registerErrorHandler(\E_ALL | \E_STRICT);

        $repository = $container->get('models')->getRepository(Shop::class);

        $shop = $repository->getActiveDefault();
        Shopware()->Container()->get('shopware.components.shop_registration_service')->registerResources($shop);

        $_SERVER['HTTP_HOST'] = $shop->getHost();

        if (!self::assertPlugin('SwagImportExport')) {
            throw new \RuntimeException('Plugin ImportExport must be installed.');
        }

        Shopware()->Db()->query(
            'UPDATE s_core_config_elements SET value = \'b:0;\' WHERE name = \'useCommaDecimal\''
        );

        Shopware()->Container()->get('cache')->clean();
    }

    /**
     * @param string $name
     *
     * @return bool
     */
    private static function assertPlugin($name)
    {
        $sql = 'SELECT 1 FROM s_core_plugins WHERE name = ? AND active = 1';

        return (bool) Shopware()->Container()->get('dbal_connection')->fetchColumn($sql, [$name]);
    }
}

ImportExportTestKernel::start();
