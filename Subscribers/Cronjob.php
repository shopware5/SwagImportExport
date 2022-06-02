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

class Cronjob implements SubscriberInterface
{
    private AutoImportServiceInterface $importService;

    public function __construct(
        AutoImportServiceInterface $importService
    ) {
        $this->importService = $importService;
    }

    public static function getSubscribedEvents(): array
    {
        return [
          'Shopware_CronJob_CronAutoImport' => 'onCronImport',
        ];
    }

    public function onCronImport(): void
    {
        $this->importService->runAutoImport();
    }
}
