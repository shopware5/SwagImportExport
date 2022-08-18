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

class ValidatorTest extends TestCase
{
    public const VALID_TYPE_RESULT = 1;
    public const INVALID_TYPE_RESULT = 0;

    private Validator $validator;

    protected function setUp(): void
    {
        $this->validator = $this->getMockForAbstractClass(Validator::class);
    }

    public function testValidateFloatShouldBeValid(): void
    {
        $validatedValue = '100';
        $result = $this->validator->validateFloat($validatedValue);

        static::assertEquals(self::VALID_TYPE_RESULT, $result);
    }

    public function testValidateFloatWithCommaSeparatedStringShouldBeValid(): void
    {
        $validatedValue = '100,2';
        $result = $this->validator->validateFloat($validatedValue);

        static::assertEquals(self::VALID_TYPE_RESULT, $result);
    }

    public function testValidateFloatWithNegativeNumberWithCommaShouldBeValid(): void
    {
        $validatedValue = '-1,2';
        $result = $this->validator->validateFloat($validatedValue);

        static::assertEquals(self::VALID_TYPE_RESULT, $result);
    }

    public function testValidateFloatWithNegativeNumberShouldBeValid(): void
    {
        $validatedValue = '-1.2';
        $result = $this->validator->validateFloat($validatedValue);

        static::assertEquals(self::VALID_TYPE_RESULT, $result);
    }

    public function testValidateFloatWithCharacterShouldBeInvalid(): void
    {
        $validatedValue = 'test';
        $result = $this->validator->validateFloat($validatedValue);

        static::assertEquals(self::INVALID_TYPE_RESULT, $result);
    }

    public function testValidateFloatWithNumbersAtTheBeginningOfAStringShouldBeInvalid(): void
    {
        $validatedValue = '123.0 abc';
        $result = $this->validator->validateFloat($validatedValue);

        static::assertEquals(self::INVALID_TYPE_RESULT, $result);
    }

    public function testValidateFloatWithNumbersAtTheEndOfAStringShouldBeInvalid(): void
    {
        $validatedValue = 'abc 123.0';
        $result = $this->validator->validateFloat($validatedValue);

        static::assertEquals(self::INVALID_TYPE_RESULT, $result);
    }

    public function testValidateFloatWithCommaButWithoutDecimalDigitsShouldBeInvalid(): void
    {
        $validatedValue = '1,';
        $result = $this->validator->validateFloat($validatedValue);

        static::assertEquals(self::INVALID_TYPE_RESULT, $result);
    }

    public function testValidateFloatWithEmptyStringShouldBeInvalid(): void
    {
        $validatedValue = '';
        $result = $this->validator->validateFloat($validatedValue);

        static::assertEquals(self::INVALID_TYPE_RESULT, $result);
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

        static::assertEquals($expectedFilteredData, $result);
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
}
