<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Shopware\Components\SwagImportExport\Transformers;

/**
 * The responsibility of this class is to modify the values of the data values due to given user small scripts.
 */
class ValuesTransformer implements DataTransformerAdapter
{
    /**
     * @var \Shopware\CustomModels\ImportExport\Expression[]
     */
    private $config;

    /**
     * @var object
     */
    private $evaluator;

    /**
     * The $config must contain the smarty or php transformation of values.
     *
     * @param $config
     */
    public function initialize($config)
    {
        $this->config = $config['expression'];
        $this->evaluator = $config['evaluator'];
    }

    /**
     * Maps the values by using the config export smarty fields and returns the new array
     *
     * @param array $data
     *
     * @return array
     */
    public function transformForward($data)
    {
        $data = $this->transform('export', $data);

        return $data;
    }

    /**
     * Changes and returns the new values, before importing
     *
     * @param array $data
     *
     * @return array
     */
    public function transformBackward($data)
    {
        $data = $this->transform('import', $data);

        return $data;
    }

    /**
     * @param string $type
     * @param array  $data
     *
     * @throws \Exception
     *
     * @return array
     */
    public function transform($type, $data)
    {
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

        foreach ($this->config as $expression) {
            $conversions[$expression->getVariable()] = $expression->{$method}();
        }

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
