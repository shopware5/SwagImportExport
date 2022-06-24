<?php
declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport\Tests\Functional\Controllers\Backend;

use Shopware\Components\Model\ModelManager;
use SwagImportExport\Models\Profile;
use SwagImportExport\Tests\Helper\ContainerTrait;
use SwagImportExport\Tests\Helper\DatabaseTestCaseTrait;
use SwagImportExport\Tests\Helper\ExportControllerTrait;

class AddressExportTest extends \Enlight_Components_Test_Controller_TestCase
{
    use ExportControllerTrait;
    use DatabaseTestCaseTrait;
    use ContainerTrait;

    public function setUp(): void
    {
        parent::setUp();

        Shopware()->Plugins()->Backend()->Auth()->setNoAuth();
        Shopware()->Plugins()->Backend()->Auth()->setNoAcl();
    }

    public function testAddressExportWithXml(): void
    {
        $params = $this->getExportRequestParams();

        /** @var ModelManager $modelManager */
        $modelManager = $this->getContainer()->get('models');
        $repo = $modelManager->getRepository(Profile::class);
        $profile = $repo->findOneBy(['name' => 'default_addresses']);

        $params['profileId'] = $profile->getId();
        $params['format'] = 'xml';

        $this->Request()->setParams($params);
        $this->dispatch('backend/SwagImportExportExport/export');

        $assigned = $this->View()->getAssign('data');

        $fileName = $assigned['fileName'];
        $file = $this->uploadPathProvider->getRealPath($fileName);

        static::assertFileExists($file);
        $this->backendControllerTestHelper->addFile($file);

        $company = $this->queryXpath($file, "//address[company='Muster GmbH']/company");
        static::assertEquals('Muster GmbH', $company->item(0)->nodeValue);

        $company = $this->queryXpath($file, "//address[company='Muster GmbH']/firstname");
        static::assertEquals('Max', $company->item(0)->nodeValue);
    }

    public function testAddressExportWithCsv(): void
    {
        $params = $this->getExportRequestParams();

        /** @var ModelManager $modelManager */
        $modelManager = $this->getContainer()->get('models');
        $repo = $modelManager->getRepository(Profile::class);
        $profile = $repo->findOneBy(['name' => 'default_addresses']);

        $params['profileId'] = $profile->getId();
        $params['format'] = 'csv';
        $this->Request()->setParams($params);

        $this->dispatch('backend/SwagImportExportExport/export');
        $assigned = $this->View()->getAssign('data');

        $fileName = $assigned['fileName'];
        $file = $this->uploadPathProvider->getRealPath($fileName);

        static::assertFileExists($file);
        $this->backendControllerTestHelper->addFile($file);

        $exportedAddresses = $this->csvToArrayIndexedByFieldValue($file, 'id');

        static::assertEquals($exportedAddresses[1]['company'], 'Muster GmbH');
        static::assertEquals($exportedAddresses[1]['firstname'], 'Max');
        static::assertEquals($exportedAddresses[1]['lastname'], 'Mustermann');
    }
}
