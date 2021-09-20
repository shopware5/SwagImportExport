<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport\Tests\Unit\Components\SwagImportExport\Validator;

use PHPUnit\Framework\TestCase;
use Shopware\Components\SwagImportExport\Exception\AdapterException;
use Shopware\Components\SwagImportExport\Validators\CategoryValidator;

class CategoryValidatorTest extends TestCase
{
    public function testItShouldThrowException()
    {
        $categoryValidator = new CategoryValidator();

        $record = [
            'parentId' => '',
        ];

        $this->expectException(AdapterException::class);
        $categoryValidator->checkRequiredFields($record);
    }
}
