<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport\Tests\Integration\Components;

use SwagImportExport\Components\Factories\DataTransformerFactory;
use SwagImportExport\Tests\Helper\ContainerTrait;
use SwagImportExport\Tests\Helper\ImportExportTestHelper;

class ExpressionEvaluatorTest extends ImportExportTestHelper
{
    use ContainerTrait;

    public function testPhpEvaluator(): void
    {
        $variables = [
            'title' => 'Product',
            'active' => true,
            'status' => 2,
        ];

        $expression1 = '$active ? false : true';
        $expression2 = '$title . \'-Test\'';

        $transformersFactory = $this->getContainer()->get(DataTransformerFactory::class);

        $phpEval = $transformersFactory->createValueConvertor('phpEvaluator');

        $evalVariable1 = $phpEval->evaluate($expression1, $variables);
        $evalVariable2 = $phpEval->evaluate($expression2, $variables);

        static::assertEquals($evalVariable1, false);
        static::assertEquals($evalVariable2, 'Product-Test');
    }

    public function testSmartyEvaluator(): void
    {
        $variables = [
            'title' => 'Product',
            'active' => true,
            'status' => 2,
        ];

        $expression1 = '{if $active} false {else} true {/if}';
        $expression2 = '{if $title == \'Product\'} {$title}-Test {/if}';

        $transformersFactory = $this->getContainer()->get(DataTransformerFactory::class);

        $smartyEval = $transformersFactory->createValueConvertor('smartyEvaluator');

        $evalVariable1 = $smartyEval->evaluate($expression1, $variables);
        $evalVariable2 = $smartyEval->evaluate($expression2, $variables);

        static::assertEquals($evalVariable1, 'false');
        static::assertEquals($evalVariable2, 'Product-Test');
    }
}
