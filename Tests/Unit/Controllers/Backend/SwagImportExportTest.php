<?php

declare(strict_types=1);

namespace SwagImportExport\Tests\Unit\Controllers\Backend;

use PHPUnit\Framework\TestCase;
use Shopware\Components\DependencyInjection\Container;
use Shopware\Components\SwagImportExport\UploadPathProvider;
use SwagImportExport\Tests\Helper\TestViewMock;
use Symfony\Component\HttpFoundation\File\UploadedFile;

require  __DIR__ . '/../../../../Controllers/Backend/SwagImportExport.php';

class SwagImportExportTest extends TestCase {

    /**
     * @dataProvider getFileInformation
     */
    public function testValidateFile(string $originalName, bool $isValid, string $extension, bool $wasSuccessFull, string $errorMessage = null) {
        $controller = new \Shopware_Controllers_Backend_SwagImportExport();
        $testView = new TestViewMock();
        $controller->setView($testView);
        $containerMock = $this->getMockBuilder(Container::class)->disableOriginalConstructor()->getMock();
        $uploadPathMock = $this->getMockBuilder(UploadPathProvider::class)->disableOriginalConstructor()->getMock();
        $containerMock->method('get')->willReturn($uploadPathMock);


        $uploadFile = $this->getMockBuilder(UploadedFile::class)->disableOriginalConstructor()->getMock();
        $uploadFile->method('isValid')->willReturn($isValid);
        $uploadFile->method('getClientOriginalExtension')->willReturn($extension);
        $uploadFile->method('getClientOriginalName')->willReturn($originalName);

        if($errorMessage) {
            $uploadFile->method('getErrorMessage')->willReturn('No file was uploaded');
        }

        $uploadFile->expects($this->exactly((int) $wasSuccessFull))->method('move');

        $controller->setContainer($containerMock);
        $request = new \Enlight_Controller_Request_RequestTestCase();
        $request->setFiles([
            'fileId' => $uploadFile
        ]);
        $controller->setRequest($request);

        $controller->uploadFileAction();
        self::assertEquals($wasSuccessFull, $testView->getAssign('success'));
    }

    /**
     * @return \Generator
     */
    public function getFileInformation() {
        yield 'With valid file' =>
            [
                'file.csv',
                true,
                'csv',
                true,
            ];
        yield 'With php file' =>
        [
            'test.php',
            true,
            'php',
            false
        ];
        yield 'With php.jpeg file' =>
        [
            'test.php.csv',
            true,
            'csv',
            false
        ];
        yield 'With invalid file' =>
        [
            'test.csv',
            false,
            'csv',
            false
        ];
    }
}
