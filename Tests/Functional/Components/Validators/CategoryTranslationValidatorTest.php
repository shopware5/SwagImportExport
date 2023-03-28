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
use SwagImportExport\Components\Exception\AdapterException;
use SwagImportExport\Components\Validators\CategoryTranslationValidator;

class CategoryTranslationValidatorTest extends TestCase
{
    public function testCheckRequiredFieldsWorksWithoutErrors(): void
    {
        $records = $this->getRecordsData();

        $categoryTranslationValidator = new CategoryTranslationValidator();
        $categoryTranslationValidator->filterEmptyString($records);

        $categoryTranslationValidator->checkRequiredFields($records);

        $this->expectNotToPerformAssertions();
    }

    public function testInvalidRecordThrowsException(): void
    {
        $records = $this->getRecordsData();

        $categoryTranslationValidator = new CategoryTranslationValidator();
        $categoryTranslationValidator->filterEmptyString($records);

        unset($records['categoryId']);
        static::expectException(AdapterException::class);

        $categoryTranslationValidator->checkRequiredFields($records);
    }

    /**
     * @return array<string, string>
     */
    private function getRecordsData(): array
    {
        return [
                'categoryId' => '1',
                'languageId' => '2',
                'description' => 'test',
                'external' => 'test',
                'externalTarget' => 'test',
                'imagePath' => 'test',
                'cmsheadline' => 'test',
                'cmstext' => 'test',
                'metatitle' => 'test',
                'metadescription' => 'test',
                'metakeywords' => 'test',
        ];
    }
}
