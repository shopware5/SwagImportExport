<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Shopware\Subscriber;

use Enlight\Event\SubscriberInterface;
use Shopware\Components\DependencyInjection\Container;
use Shopware\Components\SwagImportExport\FileIO\CsvFileReader;
use Shopware\Components\SwagImportExport\FileIO\CsvFileWriter;
use Shopware\Components\SwagImportExport\Logger\Logger;
use Shopware\Components\SwagImportExport\UploadPathProvider;
use Shopware\Components\SwagImportExport\Utils\FileHelper;

class DIContainer implements SubscriberInterface
{
    /**
     * @var array
     */
    private static $containerExtension;

    /**
     * @var Container
     */
    private $container;

    /**
     * @param Container $container
     */
    public function __construct(Container $container)
    {
        $this->container = $container;

        self::$containerExtension = [
            'swag_import_export.csv_file_writer' => $this->getCsvFileWriter(),
            'swag_import_export.csv_file_reader' => $this->getCsvFileReader(),
            'swag_import_export.logger' => $this->getLogger($this->container),
            'swag_import_export.upload_path_provider' => $this->getUploadPathProvider()
        ];
    }

    /**
     * Generate the subscribedEvents array depending on the $container static property
     *
     * @return array
     */
    public static function getSubscribedEvents()
    {
        $events = [];
        foreach (self::$containerExtension as $name => $function) {
            $events['Enlight_Bootstrap_InitResource_' . $name] = 'load';
        }
        return $events;
    }

    /**
     * Generic callback function for all registered subscribers in this class. Will dispatch the event to
     * the anonymous function of the corresponding service
     *
     * @param \Enlight_Event_EventArgs $args
     * @return mixed
     */
    public function load(\Enlight_Event_EventArgs $args)
    {
        // get registered service from event name
        $name = str_replace('Enlight_Bootstrap_InitResource_', '', $args->getName());

        // call anonymous function in order to register service
        $method = self::$containerExtension[$name];
        return $method($this->container);
    }

    /**
     * @return \Closure
     */
    private function getCsvFileWriter()
    {
        return function () {
            return new CsvFileWriter(
                new FileHelper()
            );
        };
    }

    /**
     * @param Container $container
     * @return \Closure
     */
    private function getLogger(Container $container)
    {
        return function (Container $container) {
            return new Logger(
                $container->get('swag_import_export.csv_file_writer'),
                $container->get('models')
            );
        };
    }

    /**
     * @return \Closure
     */
    private function getUploadPathProvider()
    {
        return function () {
            return new UploadPathProvider(Shopware()->DocPath());
        };
    }

    /**
     * @return \Closure
     */
    private function getCsvFileReader()
    {
        return function (Container $container) {
            return new CsvFileReader(
                $container->get('swag_import_export.upload_path_provider')
            );
        };
    }
}
