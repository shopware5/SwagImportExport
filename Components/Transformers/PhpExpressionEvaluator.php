<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport\Components\Transformers;

class PhpExpressionEvaluator implements ExpressionEvaluator
{
    /**
     * @throws \Exception
     *
     * @return mixed|void
     */
    public function evaluate(string $expression, ?array $variables)
    {
        if (empty($expression) || $expression == '') {
            return;
        }

        if ($variables === null) {
            throw new \Exception('Invalid variables passed to php evaluator');
        }

        \extract($variables);

        $errorBefore = \error_get_last();

        $evaledParam = @eval('return ' . $expression . ';');

        $errorAfter = \error_get_last();

        if ($errorAfter && ($errorBefore != $errorAfter)) {
            throw new \Exception("Error on evaluating  with expression $expression. Error message: {$errorAfter['message']}");
        }

        return $evaledParam;
    }
}
