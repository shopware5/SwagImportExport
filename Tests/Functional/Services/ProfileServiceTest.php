<?php
declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport\Tests\Functional\Services;

use Doctrine\DBAL\Connection;
use PHPUnit\Framework\TestCase;
use SwagImportExport\Components\Service\ProfileService;
use SwagImportExport\Components\UploadPathProvider;
use SwagImportExport\Tests\Helper\ContainerTrait;
use SwagImportExport\Tests\Helper\DatabaseTestCaseTrait;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class ProfileServiceTest extends TestCase
{
    use DatabaseTestCaseTrait;

    use ContainerTrait;

    public function testProfileExportShouldGiveCorrectResult(): void
    {
        $service = $this->getProfileService();
        /** @var Connection $dbalConnection */
        $dbalConnection = $this->getContainer()->get('dbal_connection');
        $profile = $dbalConnection->executeQuery('SELECT * FROM s_import_export_profile LIMIT 1')->fetch(\PDO::FETCH_ASSOC);

        $profileDataStruct = $service->exportProfile((int) $profile['id']);

        static::assertEquals($profile['name'], $profileDataStruct->getName());
        static::assertEquals($profile['type'], $profileDataStruct->getType());
        static::assertEquals($profile['tree'], \json_encode($profileDataStruct->getTree()));
    }

    public function testProfileImportShouldThrowExceptionWrongFiletype(): void
    {
        $service = $this->getProfileService();
        /** @var UploadPathProvider $uploadPathProvider */
        $uploadPathProvider = $this->getContainer()->get('swag_import_export.upload_path_provider');

        // create copy of profile.json testfile because it will be deleted by service
        \copy(__DIR__ . '/_fixtures/profile_import.json', $uploadPathProvider->getPath() . '/test.csv');

        $file = new UploadedFile($uploadPathProvider->getPath() . '/test.csv', 'test.csv');

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Die hochgeladene Datei ist keine JSON Datei.');

        $service->importProfile($file);
    }

    public function testProfileImportShouldThrowExceptionNoContent(): void
    {
        $service = $this->getProfileService();
        /** @var UploadPathProvider $uploadPathProvider */
        $uploadPathProvider = $this->getContainer()->get('swag_import_export.upload_path_provider');

        // create copy of profile.json testfile because it will be deleted by service
        \copy(__DIR__ . '/_fixtures/empty.json', $uploadPathProvider->getPath() . '/empty.json');

        $file = new UploadedFile($uploadPathProvider->getPath() . '/empty.json', 'empty.json', 'application/json');

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Die hochgeladene Datei enthält keine Daten.');

        $service->importProfile($file);
    }

    public function testProfileImportShouldThrowExceptionWrongData(): void
    {
        $service = $this->getProfileService();
        /** @var UploadPathProvider $uploadPathProvider */
        $uploadPathProvider = $this->getContainer()->get('swag_import_export.upload_path_provider');

        // create copy of profile.json testfile because it will be deleted by service
        \copy(__DIR__ . '/_fixtures/wrong.json', $uploadPathProvider->getPath() . '/wrong.json');

        $file = new UploadedFile($uploadPathProvider->getPath() . '/wrong.json', 'wrong.json', 'application/json');

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Die hochgeladenen Profildaten enthalten nicht alle benötigten Felder.');

        $service->importProfile($file);
    }

    public function testProfileImportShouldThrowExceptionEmptyValue(): void
    {
        $service = $this->getProfileService();
        /** @var UploadPathProvider $uploadPathProvider */
        $uploadPathProvider = $this->getContainer()->get('swag_import_export.upload_path_provider');

        // create copy of profile.json testfile because it will be deleted by service
        \copy(__DIR__ . '/_fixtures/empty_value.json', $uploadPathProvider->getPath() . '/empty_value.json');

        $file = new UploadedFile($uploadPathProvider->getPath() . '/empty_value.json', 'empty_value.json', 'application/json');

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Die hochgeladenen Profildaten enthalten nicht alle benötigten Felder.');

        $service->importProfile($file);
    }

    public function testProfileImportShouldSucceed(): void
    {
        $service = $this->getProfileService();
        /** @var Connection $dbalConnection */
        $dbalConnection = $this->getContainer()->get('dbal_connection');
        /** @var UploadPathProvider $uploadPathProvider */
        $uploadPathProvider = $this->getContainer()->get('swag_import_export.upload_path_provider');

        // create copy of profile.json testfile because it will be deleted by service
        \copy(__DIR__ . '/_fixtures/profile_import.json', $uploadPathProvider->getPath() . '/profile_import.json');

        $file = new UploadedFile($uploadPathProvider->getPath() . '/profile_import.json', 'profile_import.json', 'application/json');

        $service->importProfile($file);

        $importedProfile = $dbalConnection->executeQuery("SELECT * FROM s_import_export_profile WHERE `name` = 'My imported profile test'")->fetch(\PDO::FETCH_ASSOC);

        static::assertEquals('categories', $importedProfile['type']);
        static::assertEquals('{"id":"root","name":"Root","type":"node","children":[{"id":"537359399c80a","name":"Header","index":"0","type":"node","children":[{"id":"537385ed7c799","name":"HeaderChild","index":"0","type":"node"}]},{"id":"537359399c8b7","name":"categories","index":"1","type":"node","children":[{"id":"537359399c90d","name":"category","index":"0","type":"iteration","adapter":"default","children":[{"id":"53e9f539a997d","type":"leaf","index":"0","name":"categoryId","shopwareField":"categoryId"},{"id":"53e0a853f1b98","type":"leaf","index":"1","name":"parentID","shopwareField":"parentId"},{"id":"53e0cf5cad595","type":"leaf","index":"2","name":"description","shopwareField":"name"},{"id":"53e9f69bf2edb","type":"leaf","index":"3","name":"position","shopwareField":"position"},{"id":"53e0d1414b0ad","type":"leaf","index":"4","name":"metatitle","shopwareField":"metaTitle"},{"id":"53e0d1414b0d7","type":"leaf","index":"5","name":"metakeywords","shopwareField":"metaKeywords"},{"id":"53e0d17da1f06","type":"leaf","index":"6","name":"metadescription","shopwareField":"metaDescription"},{"id":"53e9f5c0eedaf","type":"leaf","index":"7","name":"cmsheadline","shopwareField":"cmsHeadline"},{"id":"53e9f5d80f10f","type":"leaf","index":"8","name":"cmstext","shopwareField":"cmsText"},{"id":"53e9f5e603ffe","type":"leaf","index":"9","name":"template","shopwareField":"template"},{"id":"53e9f5f87c87a","type":"leaf","index":"10","name":"active","shopwareField":"active"},{"id":"53e9f609c56eb","type":"leaf","index":"11","name":"blog","shopwareField":"blog"},{"id":"53e9f62a03f55","type":"leaf","index":"13","name":"external","shopwareField":"external"},{"id":"53e9f637aa1fe","type":"leaf","index":"14","name":"hidefilter","shopwareField":"hideFilter"},{"id":"541c35c378bc9","type":"leaf","index":"15","name":"attribute_attribute1","shopwareField":"attributeAttribute1"},{"id":"541c36d0bba0f","type":"leaf","index":"16","name":"attribute_attribute2","shopwareField":"attributeAttribute2"},{"id":"541c36d63fac6","type":"leaf","index":"17","name":"attribute_attribute3","shopwareField":"attributeAttribute3"},{"id":"541c36da52222","type":"leaf","index":"18","name":"attribute_attribute4","shopwareField":"attributeAttribute4"},{"id":"541c36dc540e3","type":"leaf","index":"19","name":"attribute_attribute5","shopwareField":"attributeAttribute5"},{"id":"541c36dd9e130","type":"leaf","index":"20","name":"attribute_attribute6","shopwareField":"attributeAttribute6"},{"id":"54dc86ff4bee5","name":"CustomerGroups","index":"21","type":"iteration","adapter":"customerGroups","parentKey":"categoryId","children":[{"id":"54dc87118ad11","type":"leaf","index":"0","name":"CustomerGroup","shopwareField":"customerGroupId"}]}]}]}]}', $importedProfile['tree']);
        static::assertEquals(0, $importedProfile['is_default']);
        static::assertEquals(0, $importedProfile['hidden']);
    }

    private function getProfileService(): ProfileService
    {
        return $this->getContainer()->get('swag_import_export.profile_service');
    }
}
