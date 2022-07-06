<?php
declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport\Components\Transformers;

use SwagImportExport\Components\Profile\Profile;
use SwagImportExport\Models\Expression;

/**
 * The responsibility of this class is to modify the values of the data values due to given user small scripts.
 */
class ValuesTransformer implements DataTransformerAdapter
{
    public const TYPE = 'exportConversion';

    /**
     * @var array<Expression>
     */
    private array $config;

    private ExpressionEvaluator $evaluator;

    public function __construct(ExpressionEvaluator $evaluator)
    {
        $this->evaluator = $evaluator;
    }

    public function supports(string $type): bool
    {
        return $type === self::TYPE;
    }

    /**
     * The $config must contain the smarty or php transformation of values.
     */
    public function initialize(Profile $profile): void
    {
        $this->config = $profile->getEntity()->getExpressions()->toArray();
    }

    /**
     * Maps the values by using the config export smarty fields and returns the new array
     *
     * @param array<string, array<mixed>> $data
     */
    public function transformForward(array $data): array
    {
        if (!\is_array($data)) {
            $data = [];
        }

        $conversions = [];
        foreach ($this->config as $expression) {
            $conversions[$expression->getVariable()] = $expression->getExportConversion();
        }

        return $this->handleConversion($conversions, $data);
    }

    /**
     * Changes and returns the new values, before importing
     *
     * @param array<string, array<mixed>> $data
     */
    public function transformBackward(array $data): array
    {
        if (!\is_array($data)) {
            $data = [];
        }

        $conversions = [];
        foreach ($this->config as $expression) {
            $conversions[$expression->getVariable()] = $expression->getImportConversion();
        }

        return $this->handleConversion($conversions, $data);
    }

    /**
     * @param array<string, string>              $conversions
     * @param array<array<array<string, mixed>>> $data
     *
     * @return array<array<array<string, mixed>>>
     */
    private function handleConversion(array $conversions, array $data): array
    {
        if (!empty($conversions)) {
            foreach ($data as &$records) {
                foreach ($records as &$record) {
                    foreach ($conversions as $variableName => $conversion) {
                        if (isset($record[$variableName]) && !empty($conversion)) {
                            $evalData = $this->evaluator->evaluate($conversion, $record);
                            if ($evalData || $evalData === '0') {
                                $record[$variableName] = $evalData;
                            }
                        }
                    }
                }
            }
        }

        return $data;
    }
}
