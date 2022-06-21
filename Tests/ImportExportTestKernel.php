<?php
declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Shopware\Kernel;
use Shopware\Models\Shop\Shop;

require __DIR__ . '/../../../../autoload.php';

class ImportExportTestKernel extends Kernel
{
    public const IMPORT_FILES_DIR = __DIR__ . '/Helper/ImportFiles/';

    private static ImportExportTestKernel $kernel;

    public static function start(): void
    {
        self::$kernel = new self(\getenv('SHOPWARE_ENV') ?: 'testing', true);
        self::$kernel->boot();

        $container = self::$kernel->getContainer();
        $container->get('plugins')->Core()->ErrorHandler()->registerErrorHandler(\E_ALL | \E_STRICT);

        $shop = $container->get('models')->getRepository(Shop::class)->getActiveDefault();
        $container->get('shopware.components.shop_registration_service')->registerResources($shop);

        $_SERVER['HTTP_HOST'] = $shop->getHost();

        if (!self::assertPlugin()) {
            throw new RuntimeException('Plugin ImportExport must be installed.');
        }

        $container->get('dbal_connection')->executeQuery(
            'UPDATE s_core_config_elements SET value = \'b:0;\' WHERE name = \'useCommaDecimal\''
        );

        $container->get('cache')->clean();
    }

    public static function getKernel(): ImportExportTestKernel
    {
        return self::$kernel;
    }

    private static function assertPlugin(): bool
    {
        $sql = 'SELECT 1 FROM s_core_plugins WHERE name = ? AND active = 1';

        return (bool) self::getKernel()->getContainer()->get('dbal_connection')->fetchColumn($sql, ['SwagImportExport']);
    }
}

ImportExportTestKernel::start();
