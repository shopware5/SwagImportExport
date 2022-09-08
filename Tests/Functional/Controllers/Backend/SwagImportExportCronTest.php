<?php
declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport\Tests\Functional\Controllers\Backend;

use PHPUnit\Framework\TestCase;
use SwagImportExport\Components\Service\AutoImportService;
use SwagImportExport\Controllers\Backend\Shopware_Controllers_Backend_SwagImportExportCron;
use SwagImportExport\Tests\Helper\ContainerTrait;

class SwagImportExportCronTest extends TestCase
{
    use ContainerTrait;

    public function testCronActionCanBeCalled(): void
    {
        $controller = $this->createController();

        $this->expectOutputString("No import files are found.\n");
        $controller->cronAction();
    }

    private function createController(): Shopware_Controllers_Backend_SwagImportExportCron
    {
        $controller = new Shopware_Controllers_Backend_SwagImportExportCron(
            $this->getContainer()->get('plugins'),
            $this->getContainer()->get(AutoImportService::class)
        );

        $controller->setContainer($this->getContainer());

        return $controller;
    }
}
