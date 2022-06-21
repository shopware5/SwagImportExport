<?php
declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport\Tests\Functional\Components\Validators;

use PHPUnit\Framework\TestCase;
use SwagImportExport\Components\Validators\ArticleImageValidator;

class ArticleImageValidatorTest extends TestCase
{
    public function testValidateWithoutOrderNumberShouldThrowException(): void
    {
        $validator = $this->createArticleImageValidator();
        $record = [
            'image' => $this->getImportImagePath(),
            'description' => 'testimport1',
            'thumbnail' => 1,
        ];

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Bestellnummer und Bild zwingend erforderlich.');
        $validator->checkRequiredFields($record);
    }

    public function testValidateWithoutImagePathShouldThrowException(): void
    {
        $validator = $this->createArticleImageValidator();
        $record = [
            'ordernumber' => 'SW10006',
            'description' => 'testimport1',
            'thumbnail' => 1,
        ];

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Bestellnummer und Bild zwingend erforderlich.');
        $validator->checkRequiredFields($record);
    }

    private function createArticleImageValidator(): ArticleImageValidator
    {
        return new ArticleImageValidator();
    }

    private function getImportImagePath(): string
    {
        return 'file://' . \realpath(__DIR__) . '/../../../../Helper/ImportFiles/sw-icon_blue128.png';
    }
}
