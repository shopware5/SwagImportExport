<?php
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

    /**
     * @var Validator
     */
    private $SUT;

    protected function setUp(): void
    {
        $this->SUT = $this->getMockForAbstractClass(Validator::class);
    }

    public function testValidateFloatShouldBeValid()
    {
        $validatedValue = 100;
        $result = $this->SUT->validateFloat($validatedValue);

        static::assertEquals(self::VALID_TYPE_RESULT, $result);
    }

    public function testValidateFloatWithCommaSeparatedStringShouldBeValid()
    {
        $validatedValue = '100,2';
        $result = $this->SUT->validateFloat($validatedValue);

        static::assertEquals(self::VALID_TYPE_RESULT, $result);
    }

    public function testValidateFloatWithNegativeNumberWithCommaShouldBeValid()
    {
        $validatedValue = '-1,2';
        $result = $this->SUT->validateFloat($validatedValue);

        static::assertEquals(self::VALID_TYPE_RESULT, $result);
    }

    public function testValidateFloatWithNegativeNumberShouldBeValid()
    {
        $validatedValue = -1.2;
        $result = $this->SUT->validateFloat($validatedValue);

        static::assertEquals(self::VALID_TYPE_RESULT, $result);
    }

    public function testValidateFloatWithCharacterShouldBeInvalid()
    {
        $validatedValue = 'test';
        $result = $this->SUT->validateFloat($validatedValue);

        static::assertEquals(self::INVALID_TYPE_RESULT, $result);
    }

    public function testValidateFloatWithNumbersAtTheBeginningOfAStringShouldBeInvalid()
    {
        $validatedValue = '123.0 abc';
        $result = $this->SUT->validateFloat($validatedValue);

        static::assertEquals(self::INVALID_TYPE_RESULT, $result);
    }

    public function testValidateFloatWithNumbersAtTheEndOfAStringShouldBeInvalid()
    {
        $validatedValue = 'abc 123.0';
        $result = $this->SUT->validateFloat($validatedValue);

        static::assertEquals(self::INVALID_TYPE_RESULT, $result);
    }

    public function testValidateFloatWithCommaButWithoutDecimalDigitsShouldBeInvalid()
    {
        $validatedValue = '1,';
        $result = $this->SUT->validateFloat($validatedValue);

        static::assertEquals(self::INVALID_TYPE_RESULT, $result);
    }

    public function testValidateFloatWithEmptyStringShouldBeInvalid()
    {
        $validatedValue = '';
        $result = $this->SUT->validateFloat($validatedValue);

        static::assertEquals(self::INVALID_TYPE_RESULT, $result);
    }

    public function testFilterEmptyStringShouldFilterEmptyString()
    {
        $result = $this->SUT->filterEmptyString($this->getDemoRecordForFilterEmptyStringTest());

        static::assertEmpty($result['filteredEmptyString']);
    }

    public function testFilterEmptyStringShouldNotFilterOtherDataTypes()
    {
        $expectedFilteredData = [
            'integer' => 1,
            'float' => 1.5,
            'string' => 'This is a string.',
            'boolean' => true,
        ];

        $result = $this->SUT->filterEmptyString($this->getDemoRecordForFilterEmptyStringTest());

        static::assertEquals($expectedFilteredData, $result);
    }

    /**
     * @return array
     */
    private function getDemoRecordForFilterEmptyStringTest()
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
