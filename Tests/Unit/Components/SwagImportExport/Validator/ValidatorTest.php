<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport\Tests\Unit\Components\SwagImportExport\Validator;

use Shopware\Components\SwagImportExport\Validators\Validator;

class ValidatorTest extends \PHPUnit_Framework_TestCase
{
    const VALID_TYPE_RESULT = 1;
    const INVALID_TYPE_RESULT = 0;

    /**
     * @var Validator
     */
    private $SUT;

    protected function setUp()
    {
        $this->SUT = $this->getMockForAbstractClass(Validator::class);
    }

    public function test_validate_float_should_be_valid()
    {
        $validatedValue = 100;
        $result = $this->SUT->validateFloat($validatedValue);

        $this->assertEquals(self::VALID_TYPE_RESULT, $result);
    }

    public function test_validate_float_with_comma_separated_string_should_be_valid()
    {
        $validatedValue = "100,2";
        $result = $this->SUT->validateFloat($validatedValue);

        $this->assertEquals(self::VALID_TYPE_RESULT, $result);
    }

    public function test_validate_float_with_negative_number_with_comma_should_be_valid()
    {
        $validatedValue = "-1,2";
        $result = $this->SUT->validateFloat($validatedValue);

        $this->assertEquals(self::VALID_TYPE_RESULT, $result);
    }

    public function test_validate_float_with_negative_number_should_be_valid()
    {
        $validatedValue = -1.2;
        $result = $this->SUT->validateFloat($validatedValue);

        $this->assertEquals(self::VALID_TYPE_RESULT, $result);
    }


    public function test_validate_float_with_character_should_be_invalid()
    {
        $validatedValue = "test";
        $result = $this->SUT->validateFloat($validatedValue);

        $this->assertEquals(self::INVALID_TYPE_RESULT, $result);
    }

    public function test_validate_float_with_numbers_at_the_beginning_of_a_string_should_be_invalid()
    {
        $validatedValue = "123.0 abc";
        $result = $this->SUT->validateFloat($validatedValue);

        $this->assertEquals(self::INVALID_TYPE_RESULT, $result);
    }

    public function test_validate_float_with_numbers_at_the_end_of_a_string_should_be_invalid()
    {
        $validatedValue = "abc 123.0";
        $result = $this->SUT->validateFloat($validatedValue);

        $this->assertEquals(self::INVALID_TYPE_RESULT, $result);
    }

    public function test_validate_float_with_comma_but_without_decimal_digits_should_be_invalid()
    {
        $validatedValue = "1,";
        $result = $this->SUT->validateFloat($validatedValue);

        $this->assertEquals(self::INVALID_TYPE_RESULT, $result);
    }

    public function test_validate_float_with_empty_string_should_be_invalid()
    {
        $validatedValue = "";
        $result = $this->SUT->validateFloat($validatedValue);

        $this->assertEquals(self::INVALID_TYPE_RESULT, $result);
    }

    public function test_filter_empty_string_should_filter_empty_string()
    {
        $result = $this->SUT->filterEmptyString($this->getDemoRecordForFilterEmptyStringTest());

        $this->assertEmpty($result['filteredEmptyString']);
    }

    public function test_filter_empty_string_should_not_filter_other_data_types()
    {
        $expectedFilteredData = [
            'integer' => 1,
            'float' => 1.5,
            'string' => 'This is a string.',
            'boolean' => true
        ];

        $result = $this->SUT->filterEmptyString($this->getDemoRecordForFilterEmptyStringTest());

        $this->assertEquals($expectedFilteredData, $result);
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
