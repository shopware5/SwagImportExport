<?php

declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport\Tests\Functional\Controllers\Backend;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver\Exception;
use Doctrine\DBAL\ForwardCompatibility\Result;
use SwagImportExport\Controllers\Backend\Shopware_Controllers_Backend_SwagImportExportProfile;
use SwagImportExport\Models\Profile;
use SwagImportExport\Tests\Helper\ContainerTrait;
use SwagImportExport\Tests\Helper\TestViewMock;

class SwagImportExportProfileTest extends \Enlight_Components_Test_Controller_TestCase
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

    /**
     * @throws Exception
     * @throws \Doctrine\DBAL\Exception
     */
    public function testGetColumnsIsListArrayWithConsecutiveKeys(): void
    {
        $connection = $this->getContainer()->get(Connection::class);
        $builder = $connection->createQueryBuilder();
        $builder->select('id')
            ->from('s_import_export_profile')
            ->orderBy('id', 'asc');
        $result = $builder->execute();
        static::assertInstanceOf(Result::class, $result);

        $profileIds = $result->fetchAllNumeric();

        $importExportProfile = $this->getContainer()->get(Shopware_Controllers_Backend_SwagImportExportProfile::class);
        $testView = new TestViewMock();
        $importExportProfile->setView($testView);

        $testRequest = new \Enlight_Controller_Request_RequestTestCase();

        foreach ($profileIds as $profileId) {
            $params['profileId'] = $profileId;
            $params['page'] = 0;
            $params['start'] = 0;
            $params['limit'] = 50;
            $testRequest->setParams($params);
            $importExportProfile->setRequest($testRequest);
            $importExportProfile->getColumnsAction();

            $i = 0;
            foreach (array_keys($testView->getAssign('data')) as $key) {
                static::assertSame($i, $key);
                ++$i;
            }
        }
    }
}
