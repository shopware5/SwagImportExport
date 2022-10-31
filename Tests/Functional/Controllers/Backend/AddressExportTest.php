<?php
declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport\Tests\Functional\Controllers\Backend;

use Shopware\Tests\Functional\Traits\DatabaseTransactionBehaviour;
use SwagImportExport\Models\Profile;
use SwagImportExport\Tests\Helper\ContainerTrait;
use SwagImportExport\Tests\Helper\ExportControllerTrait;

class AddressExportTest extends \Enlight_Components_Test_Controller_TestCase
{
    use ExportControllerTrait;
    use DatabaseTransactionBehaviour;
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

        $profile = $this->getContainer()->get('models')->getRepository(Profile::class)->findOneBy(['name' => 'default_addresses']);
        static::assertInstanceOf(Profile::class, $profile);
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
        $node = $company->item(0);
        static::assertInstanceOf(\DOMNode::class, $node);
        static::assertEquals('Muster GmbH', $node->nodeValue);

        $company = $this->queryXpath($file, "//address[company='Muster GmbH']/firstname");
        $node = $company->item(0);
        static::assertInstanceOf(\DOMNode::class, $node);
        static::assertEquals('Max', $node->nodeValue);
    }

    public function testAddressExportWithCsv(): void
    {
        $params = $this->getExportRequestParams();

        $profile = $this->getContainer()->get('models')->getRepository(Profile::class)->findOneBy(['name' => 'default_addresses']);
        static::assertInstanceOf(Profile::class, $profile);
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

        static::assertEquals('Muster GmbH', $exportedAddresses[1]['company']);
        static::assertEquals('Max', $exportedAddresses[1]['firstname']);
        static::assertEquals('Mustermann', $exportedAddresses[1]['lastname']);
    }
}
