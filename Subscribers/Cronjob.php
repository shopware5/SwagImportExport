<?php
declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport\Subscribers;

use Enlight\Event\SubscriberInterface;
use SwagImportExport\Components\Service\AutoImportServiceInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class Cronjob implements SubscriberInterface
{
    /**
     * @var ContainerInterface
     */
    private $container;

    public function __construct(
        ContainerInterface $container
    ) {
        $this->container = $container;
    }

    public static function getSubscribedEvents(): array
    {
        return [
          'Shopware_CronJob_CronAutoImport' => 'onCronImport',
        ];
    }

    public function onCronImport(): void
    {
        /** @var AutoImportServiceInterface $importService */
        $importService = $this->container->get('swag_import_export.auto_importer');
        $importService->runAutoImport();
    }
}
