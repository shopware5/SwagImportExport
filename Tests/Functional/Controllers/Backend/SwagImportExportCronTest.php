<?php

declare(strict_types=1);
/**
 * Shopware 5
 * Copyright (c) shopware AG
 *
 * According to our dual licensing model, this program can be used either
 * under the terms of the GNU Affero General Public License, version 3,
 * or under a proprietary license.
 *
 * The texts of the GNU Affero General Public License with an additional
 * permission and of our proprietary license can be found at and
 * in the LICENSE file you have received along with this program.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * "Shopware" is a registered trademark of shopware AG.
 * The licensing of the program under the AGPLv3 does not imply a
 * trademark license. Therefore any rights, title and interest in
 * our trademarks remain entirely with us.
 */

namespace SwagImportExport\Tests\Functional\Controllers\Backend;

use Doctrine\DBAL\Connection;
use PHPUnit\Framework\TestCase;
use SwagImportExport\Components\Service\AutoImportService;
use SwagImportExport\Controllers\Backend\Shopware_Controllers_Backend_SwagImportExportCron;
use SwagImportExport\Tests\Helper\ContainerTrait;

class SwagImportExportCronTest extends TestCase
{
    use ContainerTrait;
    public const IMPORT_CRON_PATH = __DIR__ . '/../../../../../../../files/import_cron/';

    public function testCronActionCanBeCalled(): void
    {
        $controller = $this->createController();

        $this->expectOutputString("No import files are found.\n");
        $controller->cronAction();
    }

    public function testCronCanImportImagesOverUrl(): void
    {
        $conn = $this->getContainer()->get(Connection::class);

        $fileName = 'default_article_images_url.xml';
        $filePath = realpath(__DIR__ . '/_fixtures/' . $fileName);
        $cronPath = realpath(self::IMPORT_CRON_PATH);

        static::assertTrue(copy($filePath, $cronPath . '/' . $fileName));
        static::assertFileExists($cronPath . '/' . $fileName);

        $controller = $this->getContainer()->get(AutoImportService::class);
        $controller->runAutoImport();

        $sql = "SELECT articleID FROM s_articles_img WHERE img LIKE 'test-bild%'";
        $result = $conn->executeQuery($sql)->fetchOne();

        unlink($cronPath . "../import_export/default_article_images_url.xml-articlesImages-swag.csv");

        static::assertIsString($result);
        static::assertSame('3', $result);
        static::assertFileDoesNotExist(self::IMPORT_CRON_PATH . $fileName);
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
