<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Functional\Components\SwagImportExport\Validators;

use PHPUnit\Framework\TestCase;
use Shopware\Components\SwagImportExport\Validators\ArticleImageValidator;

class ArticleImageValidatorTest extends TestCase
{
    public function testValidateWithoutOrdernumberShouldThrowException()
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

    public function testValidateWithoutImagePathShouldThrowException()
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

    /**
     * @return ArticleImageValidator
     */
    private function createArticleImageValidator()
    {
        return new ArticleImageValidator();
    }

    /**
     * @return string
     */
    private function getImportImagePath()
    {
        return 'file://' . \realpath(__DIR__) . '/../../../../Helper/ImportFiles/sw-icon_blue128.png';
    }
}
