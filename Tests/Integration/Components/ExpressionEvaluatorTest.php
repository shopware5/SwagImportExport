<?php
declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport\Tests\Integration\Components;

use PHPUnit\Framework\TestCase;
use SwagImportExport\Components\Transformers\SmartyExpressionEvaluator;
use SwagImportExport\Tests\Helper\ContainerTrait;
use SwagImportExport\Tests\Helper\DatabaseTestCaseTrait;

class ExpressionEvaluatorTest extends TestCase
{
    use DatabaseTestCaseTrait;
    use ContainerTrait;

    public function testSmartyEvaluator(): void
    {
        $variables = [
            'title' => 'Product',
            'active' => true,
            'status' => 2,
        ];

        $expression1 = '{if $active} false {else} true {/if}';
        $expression2 = '{if $title == \'Product\'} {$title}-Test {/if}';

        $smartyEval = new SmartyExpressionEvaluator($this->getContainer()->get('template'));

        $evalVariable1 = $smartyEval->evaluate($expression1, $variables);
        $evalVariable2 = $smartyEval->evaluate($expression2, $variables);

        static::assertEquals('false', $evalVariable1);
        static::assertEquals('Product-Test', $evalVariable2);
    }
}
