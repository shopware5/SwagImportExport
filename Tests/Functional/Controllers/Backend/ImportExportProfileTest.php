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
use SwagImportExport\Tests\Helper\ContainerTrait;
use SwagImportExport\Tests\Helper\DatabaseTestCaseTrait;
use Symfony\Component\HttpFoundation\Request;

class ImportExportProfileTest extends TestCase
{
    use DatabaseTestCaseTrait;
    use ContainerTrait;

    public function testExportProfile(): void
    {
        $profileController = $this->getProfileController();
        $profileController->setResponse(
            new \Enlight_Controller_Response_ResponseTestCase()
        );

        $request = new Request([
            'profileId' => '1',
        ]);

        ob_start();
        $profileController->exportProfileAction($request);

        $content = ob_get_clean();

        static::assertIsString($content);

        $profile = json_decode($content, true);

        static::assertEquals('default_categories_minimal', $profile['name']);
        static::assertEquals('categories', $profile['type']);
    }

    public function getProfileController(): Shopware_Controllers_Backend_SwagImportExportProfile
    {
        return $this->getContainer()->get(Shopware_Controllers_Backend_SwagImportExportProfile::class);
    }
}
