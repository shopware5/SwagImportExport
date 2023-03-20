<?php

declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport\Tests\Unit\Controllers\Backend;

use PHPUnit\Framework\TestCase;
use SwagImportExport\Components\UploadPathProvider;
use SwagImportExport\Controllers\Backend\Shopware_Controllers_Backend_SwagImportExport;
use SwagImportExport\Tests\Helper\TestViewMock;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class SwagImportExportTest extends TestCase
{
    /**
     * @dataProvider getFileInformation
     */
    public function testValidateFile(
        string $originalName,
        bool $isValid,
        string $extension,
        bool $wasSuccessFull,
        string $errorMessage = null
    ): void {
        $testView = new TestViewMock();
        $controller = new Shopware_Controllers_Backend_SwagImportExport(new UploadPathProvider(__DIR__));
        $controller->setView($testView);
        $uploadFile = $this->getMockBuilder(UploadedFile::class)->disableOriginalConstructor()->getMock();
        $uploadFile->method('isValid')->willReturn($isValid);
        $uploadFile->method('getClientOriginalExtension')->willReturn($extension);
        $uploadFile->method('getClientOriginalName')->willReturn($originalName);

        if ($errorMessage) {
            $uploadFile->method('getErrorMessage')->willReturn('No file was uploaded');
        }

        $uploadFile->expects(static::exactly((int) $wasSuccessFull))->method('move');

        $request = new \Enlight_Controller_Request_RequestTestCase();
        $request->setFiles([
            'fileId' => $uploadFile,
        ]);
        $controller->setRequest($request);

        $controller->uploadFileAction();
        static::assertSame($wasSuccessFull, $testView->getAssign('success'));

        if ($wasSuccessFull) {
            $fileName = $testView->getAssign('data')['fileName'];
            static::assertStringStartsWith('import.file-is-valid.' . (new \DateTime('now'))->format('Y.m.d.h.i'), $fileName);
            static::assertStringEndsWith('.csv', $fileName);
        }
    }

    public function getFileInformation(): \Generator
    {
        yield 'With valid file' => [
                'file-is-valid.csv',
                true,
                'csv',
                true,
            ];
        yield 'With php file' => [
            'test.php',
            true,
            'php',
            false,
        ];
        yield 'With invalid file' => [
            'test.csv',
            false,
            'csv',
            false,
        ];
    }
}
