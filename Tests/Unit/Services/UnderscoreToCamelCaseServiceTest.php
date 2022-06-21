<?php
declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport\Tests\Unit\Services;

use PHPUnit\Framework\TestCase;
use SwagImportExport\Components\Service\UnderscoreToCamelCaseService;

class UnderscoreToCamelCaseServiceTest extends TestCase
{
    /**
     * @dataProvider underscoreToCamelCaseTest_provider
     */
    public function testUnderscoreToCamelCase($string, string $expectedResult): void
    {
        $service = new UnderscoreToCamelCaseService();

        $result = $service->underscoreToCamelCase($string);

        static::assertSame($expectedResult, $result);
    }

    public function underscoreToCamelCaseTest_provider(): array
    {
        return [
            [null, ''],
            ['', ''],
            ['234', '234'],
            ['234.123', '234.123'],
            ['foo_10_bar', 'foo_10Bar'],
            ['foo_10_bar_this_is_1_test', 'foo_10BarThisIs_1Test'],
            ['foo_10_bar_this_is_a_2._test', 'foo_10BarThisIsA_2.Test'],
            ['this_is_a_test', 'thisIsATest'],
            ['thisIs_a_test', 'thisIsATest'],
            ['this is_a_test', 'this isATest'],
            ['this is a_test', 'this is aTest'],
            ['this is a test', 'this is a test'],
            ['one_more_test', 'oneMoreTest'],
        ];
    }
}
