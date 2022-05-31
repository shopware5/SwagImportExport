<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport\Components\Transformers;

interface ExpressionEvaluator
{
    /**
     * @param array<string, mixed>|null $variables
     */
    public function evaluate(string $expression, ?array $variables);
}
