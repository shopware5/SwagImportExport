<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport\Tests\Functional\Components\Transformers;

use PHPUnit\Framework\TestCase;
use SwagImportExport\Components\Transformers\ExpressionEvaluator;
use SwagImportExport\Components\Transformers\SmartyExpressionEvaluator;
use SwagImportExport\Components\Transformers\ValuesTransformer;
use SwagImportExport\CustomModels\Expression;

class ValuesTransformerTest extends TestCase
{
    /**
     * @dataProvider transform_test_dataProvider
     *
     * @param array<string, mixed>|null $data
     * @param array<string, mixed>|null $expectedResult
     */
    public function testTransform(?string $type, ?array $data, bool $expectException = false, ?array $expectedResult = null, ?ExpressionEvaluator $evaluator = null): void
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
     * @return array<array<mixed>>
     */
    public function transform_test_dataProvider(): array
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

    private function getValuesTransformer(?ExpressionEvaluator $evaluator): ValuesTransformer
    {
        $expression1 = new Expression();
        $expression1->fromArray(['id' => 1, 'variable' => 'testVar', 'importConversion' => 'importConversion', 'exportConversion' => 'exportConversion']);

        $expression2 = new Expression();
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
