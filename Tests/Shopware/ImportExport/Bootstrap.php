<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Shopware\Components\SwagImportExport\FileIO\CsvFileReader;
use Shopware\Components\SwagImportExport\FileIO\CsvFileWriter;
use Shopware\Components\SwagImportExport\Logger\Logger;
use Shopware\Components\SwagImportExport\UploadPathProvider;
use Shopware\Components\SwagImportExport\Utils\FileHelper;

include_once __DIR__ . '/../../../../../../../../../tests/Functional/bootstrap.php';

class PluginTestKernel extends TestKernel
{
    public static function start()
    {
        parent::start();

        if (!self::assertPlugin('SwagImportExport')) {
            echo "Plugin SwagImportExport is not active." . PHP_EOL;
            exit();
        }

        Shopware()->Loader()->registerNamespace('Tests\Helper', __DIR__ . '/../../Helper/');
        Shopware()->Loader()->registerNamespace('Tests\Shopware\ImportExport', __DIR__ . '/');
        Shopware()->Loader()->registerNamespace('Shopware\Subscriber', __DIR__ . '/../../../Subscriber/');

        self::registerResources();
    }

    /**
     * Registers all necessary classes to the di container.
     */
    private static function registerResources()
    {
        Shopware()->Container()->set('swag_import_export.logger',
            new Logger(new CsvFileWriter(new FileHelper()), Shopware()->Models())
        );

        Shopware()->Container()->set('swag_import_export.upload_path_provider',
            new UploadPathProvider(Shopware()->DocPath())
        );

        Shopware()->Container()->set('swag_import_export.csv_file_writer',
            new CsvFileWriter(new FileHelper())
        );

        Shopware()->Container()->set('swag_import_export.csv_file_reader',
            new CsvFileReader(
                Shopware()->Container()->get('swag_import_export.upload_path_provider')
            )
        );
    }

    /**
     * @param string $name
     * @return boolean
     */
    private static function assertPlugin($name)
    {
        $sql = 'SELECT 1 FROM s_core_plugins WHERE name = ? AND active = 1';
        return (boolean) Shopware()->Container()->get('dbal_connection')->fetchColumn($sql, [ $name ]);
    }
}

PluginTestKernel::start();
