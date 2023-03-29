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
use SwagImportExport\Controllers\Backend\Shopware_Controllers_Backend_SwagImportExportProfile;
use SwagImportExport\Models\Profile;
use SwagImportExport\Tests\Helper\ContainerTrait;
use SwagImportExport\Tests\Helper\TestViewMock;

class SwagImportExportProfileTest extends TestCase
{
    use ContainerTrait;

    public function testGetColumnsFiltersBadColumns(): void
    {
        $swagImportExportProfile = $this->getContainer()->get(Shopware_Controllers_Backend_SwagImportExportProfile::class);
        $swagImportExportProfile->setView(new TestViewMock());
        $profile = $this->getContainer()->get('models')->getRepository(Profile::class)->findOneBy(['name' => 'default_article_prices']);
        static::assertInstanceOf(Profile::class, $profile);
        $params['profileId'] = $profile->getId();

        $swagImportExportProfile->setRequest(new \Enlight_Controller_Request_RequestTestCase($params));
        $swagImportExportProfile->getColumnsAction();
        $columns = $swagImportExportProfile->View()->getAssign('data');

        foreach ($columns as $column) {
            static::assertIsArray($column);
            static::assertArrayHasKey('id', $column);
            static::assertArrayHasKey('name', $column);
        }
    }
}
