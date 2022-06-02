<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport\Tests\Unit\Components\Validator;

use PHPUnit\Framework\TestCase;
use SwagImportExport\Components\Exception\AdapterException;
use SwagImportExport\Components\Validators\CategoryValidator;

class CategoryValidatorTest extends TestCase
{
    public function testItShouldThrowException(): void
    {
        $categoryValidator = new CategoryValidator();

        $record = [
            'parentId' => '',
        ];

        $this->expectException(AdapterException::class);
        $categoryValidator->checkRequiredFields($record);
    }
}
