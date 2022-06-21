<?php
declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport\Components\Transformers;

use SwagImportExport\CustomModels\Expression;

/**
 * The responsibility of this class is to modify the values of the data values due to given user small scripts.
 */
class ValuesTransformer implements DataTransformerAdapter
{
    /**
     * @var array<Expression>
     */
    private ?iterable $config = null;

    private ?ExpressionEvaluator $evaluator;

    /**
     * The $config must contain the smarty or php transformation of values.
     *
     * @param array{expression: array<Expression>, evaluator: ExpressionEvaluator} $config
     */
    public function initialize($config): void
    {
        $this->config = $config['expression'];
        $this->evaluator = $config['evaluator'];
    }

    /**
     * Maps the values by using the config export smarty fields and returns the new array
     *
     * @param array<string, array<mixed>> $data
     */
    public function transformForward(array $data): array
    {
        $data = $this->transform('export', $data);

        return $data;
    }

    /**
     * Changes and returns the new values, before importing
     *
     * @param array<string, array<mixed>> $data
     */
    public function transformBackward(array $data): array
    {
        $data = $this->transform('import', $data);

        return $data;
    }

    /**
     * @param array<string, array<mixed>> $data
     */
    public function transform(?string $type, ?array $data): array
    {
        if (!\is_array($data)) {
            $data = [];
        }

        $conversions = [];

        switch ($type) {
            case 'export':
                $method = 'getExportConversion';
                break;
            case 'import':
                $method = 'getImportConversion';
                break;
            default:
                throw new \Exception("Convert type $type does not exist.");
        }

        if (!\is_array($this->config)) {
            return $data;
        }

        foreach ($this->config as $expression) {
            $conversions[$expression->getVariable()] = $expression->{$method}();
        }

        if (!empty($conversions)) {
            foreach ($data as &$records) {
                foreach ($records as &$record) {
                    foreach ($conversions as $variableName => $conversion) {
                        if (!$this->evaluator) {
                            throw new \Exception('Evaluator is not set');
                        }

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
