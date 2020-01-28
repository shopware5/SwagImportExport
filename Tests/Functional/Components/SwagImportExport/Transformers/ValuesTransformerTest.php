<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use PHPUnit\Framework\TestCase;
use Shopware\Components\SwagImportExport\Transformers\SmartyExpressionEvaluator;
use Shopware\Components\SwagImportExport\Transformers\ValuesTransformer;

class ValuesTransformerTest extends TestCase
{
    /**
     * @dataProvider transform_test_dataProvider
     *
     * @param null $type
     * @param null $data
     * @param bool $expectException
     * @param null $expectedResult
     * @param null $evaluator
     */
    public function test_transform($type = null, $data = null, $expectException = false, $expectedResult = null, $evaluator = null)
    {
        $transformer = $this->getValuesTransformer($evaluator);

        if ($expectException) {
            $this->expectException(\Exception::class);

            $transformer->transform($type, $data);

            return;
        }

        $result = $transformer->transform($type, $data);

        static::assertSame($expectedResult, $result);
    }

    /**
     * @return array
     */
    public function transform_test_dataProvider()
    {
        $data = [
            [['testVar' => 'someValue'], ['otherTestVar' => 'someValue']],
        ];

        $evaluator1 = $this->getMockBuilder(SmartyExpressionEvaluator::class)->disableOriginalConstructor()->getMock();
        $evaluator1->method('evaluate')->willReturn('0');

        $evaluator2 = $this->getMockBuilder(SmartyExpressionEvaluator::class)->disableOriginalConstructor()->getMock();
        $evaluator2->method('evaluate')->willReturn('1');

        return [
            [null, null, true],
            ['anyType', null, true],
            ['otherType', [], true],
            ['import', [], false, []],
            ['export', [], false, []],
            ['import', $data, false, [[['testVar' => '0'], ['otherTestVar' => '0']]], $evaluator1],
            ['export', $data, false, [[['testVar' => '0'], ['otherTestVar' => '0']]], $evaluator1],
            ['import', $data, false, [[['testVar' => '1'], ['otherTestVar' => '1']]], $evaluator2],
            ['export', $data, false, [[['testVar' => '1'], ['otherTestVar' => '1']]], $evaluator2],
        ];
    }

    /**
     * @return ValuesTransformer
     */
    private function getValuesTransformer($evaluator)
    {
        $expression1 = new \Shopware\CustomModels\ImportExport\Expression();
        $expression1->fromArray(['id' => 1, 'variable' => 'testVar', 'importConversion' => 'importConversion', 'exportConversion' => 'exportConversion']);

        $expression2 = new \Shopware\CustomModels\ImportExport\Expression();
        $expression2->fromArray(['id' => 2, 'variable' => 'otherTestVar', 'importConversion' => 'importConversion1', 'exportConversion' => 'exportConversion1']);

        $config['evaluator'] = $evaluator;
        $config['expression'] = [
            $expression1,
            $expression2,
        ];

        $transformer = new ValuesTransformer();

        $transformer->initialize($config);

        return $transformer;
    }
}
