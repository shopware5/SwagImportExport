<?php
declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport\Tests\Unit\Components\Validator;

use PHPUnit\Framework\TestCase;
use SwagImportExport\Components\Validators\Validator;
use SwagImportExport\Tests\Helper\ReflectionHelperTrait;

class ValidatorTest extends TestCase
{
    use ReflectionHelperTrait;

    public const VALID_TYPE_RESULT = true;
    public const INVALID_TYPE_RESULT = false;

    private Validator $validator;

    protected function setUp(): void
    {
        $this->validator = $this->getMockForAbstractClass(Validator::class);
    }

    public function testValidateFloatShouldBeValid(): void
    {
        static::assertSame(self::VALID_TYPE_RESULT, $this->assertFloatValue('100'));
    }

    public function testValidateFloatWithCommaSeparatedStringShouldBeValid(): void
    {
        static::assertSame(self::VALID_TYPE_RESULT, $this->assertFloatValue('100,2'));
    }

    public function testValidateFloatWithNegativeNumberWithCommaShouldBeValid(): void
    {
        static::assertSame(self::VALID_TYPE_RESULT, $this->assertFloatValue('-1,2'));
    }

    public function testValidateFloatWithNegativeNumberShouldBeValid(): void
    {
        static::assertSame(self::VALID_TYPE_RESULT, $this->assertFloatValue('-1.2'));
    }

    public function testValidateFloatWithCharacterShouldBeInvalid(): void
    {
        static::assertSame(self::INVALID_TYPE_RESULT, $this->assertFloatValue('test'));
    }

    public function testValidateFloatWithNumbersAtTheBeginningOfAStringShouldBeInvalid(): void
    {
        static::assertSame(self::INVALID_TYPE_RESULT, $this->assertFloatValue('123.0 abc'));
    }

    public function testValidateFloatWithNumbersAtTheEndOfAStringShouldBeInvalid(): void
    {
        static::assertSame(self::INVALID_TYPE_RESULT, $this->assertFloatValue('abc 123.0'));
    }

    public function testValidateFloatWithCommaButWithoutDecimalDigitsShouldBeInvalid(): void
    {
        static::assertSame(self::INVALID_TYPE_RESULT, $this->assertFloatValue('1,'));
    }

    public function testValidateFloatWithEmptyStringShouldBeInvalid(): void
    {
        static::assertSame(self::INVALID_TYPE_RESULT, $this->assertFloatValue(''));
    }

    public function testFilterEmptyStringShouldFilterEmptyString(): void
    {
        $result = $this->validator->filterEmptyString($this->getDemoRecordForFilterEmptyStringTest());

        static::assertEmpty($result['filteredEmptyString']);
    }

    public function testFilterEmptyStringShouldNotFilterOtherDataTypes(): void
    {
        $expectedFilteredData = [
            'integer' => 1,
            'float' => 1.5,
            'string' => 'This is a string.',
            'boolean' => true,
        ];

        $result = $this->validator->filterEmptyString($this->getDemoRecordForFilterEmptyStringTest());

        static::assertSame($expectedFilteredData, $result);
    }

    /**
     * @return array<string, mixed>
     */
    private function getDemoRecordForFilterEmptyStringTest(): array
    {
        return [
            'integer' => 1,
            'float' => 1.5,
            'string' => 'This is a string.',
            'filteredEmptyString' => '',
            'boolean' => true,
        ];
    }

    private function assertFloatValue(string $validatedValue): bool
    {
        $reflectionMethod = $this->getReflectionMethod(Validator::class, 'validateFloat');

        return $reflectionMethod->invoke($this->validator, $validatedValue);
    }
}
