<?php
declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport\Components\Transformers;

use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Shopware\Models\Customer\Group;
use Shopware\Models\Shop\Shop;
use SwagImportExport\Components\Utils\SnippetsHelper;

/**
 * The responsibility of this class is to restructure the flat array to tree and vise ver
 */
class FlattenTransformer implements DataTransformerAdapter, ComposerInterface
{
    protected ?string $config = null;

    /**
     * @var array<string, mixed>|null
     */
    protected ?array $mainIterationPart = null;

    protected ?string $mainAdapter = null;

    /**
     * @var array<string, mixed>
     */
    protected array $iterationParts = [];

    protected array $iterationTempData = [];

    /**
     * @var array<string|int, mixed>
     */
    protected array $tempData = [];

    /**
     * @var array<string|int, mixed>
     */
    protected array $tempMapper = [];

    /**
     * @var array<string, mixed>|null
     */
    protected ?array $translationColumns = null;

    /**
     * Sets the config that has the tree structure
     */
    public function initialize($config): void
    {
        $this->config = $config;
    }

    /**
     * Transforms the flat array into tree with list of nodes containing children and attributes.
     *
     * @param array<string, array<mixed>> $data
     *
     * @throws \Enlight_Event_Exception
     */
    public function transformForward(array $data): array
    {
        $mainNode = $this->getMainIterationPart();
        $this->processIterationParts($mainNode);

        $nodeName = $mainNode['name'];
        $flatData = [];

        foreach ($data[$nodeName] as $record) {
            $this->resetTempData();
            $this->collectData($record, $nodeName);
            $flatData[] = $this->getTempData();
        }

        return Shopware()->Events()->filter(
            'Shopware_Components_SwagImportExport_Transformers_FlattenTransformer_TransformForward',
            $flatData,
            ['subject' => $this]
        );
    }

    /**
     * Transforms a list of nodes containing children and attributes into flat array.
     *
     * @param array<string, mixed> $data
     */
    public function transformBackward(array $data): array
    {
        $data = Shopware()->Events()->filter(
            'Shopware_Components_SwagImportExport_Transformers_FlattenTransformer_TransformBackward',
            $data,
            ['subject' => $this]
        );

        $mainNode = $this->getMainIterationPart();
        $this->processIterationParts($mainNode);
        $tree = [];

        foreach ($data as $row) {
            $tree[] = $this->transformToTree($mainNode, $row, $mainNode['name']);
        }

        return $tree;
    }

    /**
     * Composes a header column names based on config
     */
    public function composeHeader(): array
    {
        $mainNode = $this->getMainIterationPart();
        $this->processIterationParts($mainNode);

        $transformData = $this->transform($mainNode);
        $this->collectHeader($transformData, $mainNode['name']);

        return $this->getTempData();
    }

    /**
     * Composes a tree footer based on config
     *
     * @retrun array{}
     */
    public function composeFooter(): array
    {
        return [];
    }

    /**
     * @param array<string, mixed> $tree
     *
     * Search the iteration part of the tree template
     */
    public function findMainIterationPart(array $tree): void
    {
        foreach ($tree as $key => $value) {
            if ($key === 'adapter') {
                $this->mainIterationPart = $tree;

                return;
            }

            if (\is_array($value)) {
                $this->findMainIterationPart($value);
            }
        }
    }

    /**
     * Preparing/Modifying nodes to converting into xml
     * and puts db data into this formatted array
     *
     * @param array<string, mixed> $node
     * @param array<string, mixed> $mapper
     */
    public function transform(array $node, ?array $mapper = [])
    {
        if (isset($node['children'])) {
            if (isset($node['attributes'])) {
                foreach ($node['attributes'] as $attribute) {
                    $currentNode['_attributes'][$attribute['name']] = $mapper[$attribute['shopwareField']];
                }
            }

            foreach ($node['children'] as $child) {
                $currentNode[$child['name']] = $this->transform($child, $mapper);
            }
        } else {
            if (isset($node['attributes'])) {
                foreach ($node['attributes'] as $attribute) {
                    $currentNode['_attributes'][$attribute['name']] = $mapper[$attribute['shopwareField']];
                }

                $currentNode['_value'] = $mapper[$node['shopwareField']];
            } else {
                $currentNode = $mapper[$node['shopwareField']];
            }
        }

        return $currentNode;
    }

    /**
     * Creates and returns mapper of provided profile node
     *
     * @param array<string, mixed> $node
     */
    public function createMapperFromProfile(array $node)
    {
        if (isset($node['children'])) {
            if (isset($node['attributes'])) {
                foreach ($node['attributes'] as $attribute) {
                    $currentNode['_attributes'][$attribute['name']] = $attribute['shopwareField'];
                }
            }

            foreach ($node['children'] as $child) {
                $currentNode[$child['name']] = $this->createMapperFromProfile($child);
            }
        } else {
            if (isset($node['attributes'])) {
                foreach ($node['attributes'] as $attribute) {
                    $currentNode['_attributes'][$attribute['name']] = $attribute['shopwareField'];
                }

                $currentNode['_value'] = $node['shopwareField'];
            } else {
                $currentNode = $node['shopwareField'];
            }
        }

        return $currentNode;
    }

    /**
     * Transform flat data into tree array
     *
     * @param array<string, mixed> $data
     * @param array<string, mixed> $node
     *
     * @throws \Exception
     */
    public function transformToTree(array $node, array $data, ?string $nodePath = null)
    {
        if (isset($this->iterationParts[$nodePath])) { // iteration
            if ($node['adapter'] == 'price') {
                // find name of column with *price* values
                $priceColumnName = $this->findNodeByShopwareField($node, 'price');
                if (!\is_string($priceColumnName)) {
                    throw new \Exception('Price column not found');
                }

                $dataColumns = \array_keys($data);
                $isEkGroupMissing = false;
                $prices = [];
                $matches = [];
                $groups = [];

                // find groups
                $priceColumns = \preg_grep('/^' . $priceColumnName . '_+(.*)/i', $dataColumns);
                foreach ($priceColumns as $columns) {
                    \preg_match('/' . $priceColumnName . '_+(?P<group>.*)$/i', $columns, $matches);
                    $groups[] = $matches['group'];
                }

                // special case for EK group ('_EK' may be missing)
                if (!\in_array('EK', $groups)) {
                    \array_unshift($groups, 'EK');
                    $isEkGroupMissing = true;
                }

                // TODO: add filters here

                // extract values
                foreach ($groups as $group) {
                    // special case for EK group ('_EK' may be missing)
                    if ($group == 'EK' && $isEkGroupMissing) {
                        $group = '';
                    }
                    $prices[] = $this->transformPricesToTree($node, $data, $group);
                }

                return $prices;
            } elseif ($node['adapter'] == 'configurator') {
                // find fields
                $columnMapper = [
                    'configOptionName' => $this->findNodeByShopwareField($node, 'configOptionName'),
                    'configOptionPosition' => $this->findNodeByShopwareField($node, 'configOptionPosition'),
                    'configOptionId' => $this->findNodeByShopwareField($node, 'configOptionId'),
                    'configGroupName' => $this->findNodeByShopwareField($node, 'configGroupName'),
                    'configSetName' => $this->findNodeByShopwareField($node, 'configSetName'),
                    'configSetId' => $this->findNodeByShopwareField($node, 'configSetId'),
                    'configSetType' => $this->findNodeByShopwareField($node, 'configSetType'),
                ];

                if (!\is_string($columnMapper['configOptionId'])) {
                    if (!\is_string($columnMapper['configOptionName'])) {
                        throw new \Exception('configOptionName column not found');
                    }
                    if (!\is_string($columnMapper['configGroupName'])) {
                        throw new \Exception('configGroupName column not found');
                    }
                }

                $separator = '|';
                if (\is_string($columnMapper['configSetId'])) {
                    $configSetId = \explode($separator, $this->getDataValue($data, $columnMapper['configSetId']));
                    $configSetId = $this->getFirstElement($configSetId);
                }

                if (\is_string($columnMapper['configSetType'])) {
                    $configSetType = \explode($separator, $this->getDataValue($data, $columnMapper['configSetType']));
                    $configSetType = $this->getFirstElement($configSetType);
                }

                if (\is_string($columnMapper['configSetName'])) {
                    $setNames = \explode($separator, $this->getDataValue($data, $columnMapper['configSetName']));
                    $setNames = $this->getFirstElement($setNames);
                }

                if (\is_string($columnMapper['configOptionPosition'])) {
                    $positions = \explode($separator, $this->getDataValue($data, $columnMapper['configOptionPosition']));
                }

                $configs = [];
                $values = [];
                $optionIds = [];

                if (\is_string($columnMapper['configOptionName'])) {
                    $values = \explode($separator, $this->getDataValue($data, $columnMapper['configOptionName']));
                }

                if (\is_string($columnMapper['configOptionId'])) {
                    $optionIds = \explode($separator, $this->getDataValue($data, $columnMapper['configOptionId']));
                }

                // creates configOptionId to have more priority than configOptionName
                $counter = \is_string($columnMapper['configOptionId']) ? \count($optionIds) : \count($values);

                for ($i = 0; $i < $counter; ++$i) {
                    if (\strstr($values[$i], '::')) {
                        $message = SnippetsHelper::getNamespace()
                            ->get('transformers/used_colon', "In the group name, is used a colon ':'. Please delete it and try again.");
                        throw new \Exception($message);
                    }

                    $value = \explode(':', $values[$i]);
                    $configs[] = $this->transformConfiguratorToTree(
                        $node,
                        [
                            $columnMapper['configGroupName'] => $value[0],
                            $columnMapper['configOptionName'] => $value[1],
                            $columnMapper['configOptionPosition'] => $positions[$i],
                            $columnMapper['configOptionId'] => $optionIds[$i],
                            $columnMapper['configSetId'] => $configSetId,
                            $columnMapper['configSetType'] => $configSetType,
                            $columnMapper['configSetName'] => $setNames,
                        ]
                    );
                }

                return $configs;
            } elseif ($node['adapter'] == 'propertyValue') {
                $mapper = $this->createMapperFromProfile($node);

                $columnMapper = [
                    'propertyValueId' => $this->findNodeByShopwareField($node, 'propertyValueId'),
                    'propertyValueName' => $this->findNodeByShopwareField($node, 'propertyValueName'),
                    'propertyOptionName' => $this->findNodeByShopwareField($node, 'propertyOptionName'),
                    'propertyGroupName' => $this->findNodeByShopwareField($node, 'propertyGroupName'),
                ];

                if (!\is_string($columnMapper['propertyValueId'])) {
                    if (!\is_string($columnMapper['propertyValueName'])) {
                        throw new \Exception('propertyValueName column not found');
                    }
                    if (!\is_string($columnMapper['propertyOptionName'])) {
                        throw new \Exception('propertyOptionName column not found');
                    }
                }

                foreach ($mapper as $key => $value) {
                    if ($value === 'propertyGroupName') {
                        $propertyGroupName = $this->getDataValue($data, $key);
                    } elseif ($value === 'propertyGroupId') {
                    } else {
                        $collectedData[$key] = \explode('|', $this->getDataValue($data, $key));
                    }
                }

                unset($collectedData[$columnMapper['propertyOptionName']]);

                $newData = [];
                if (\is_string($columnMapper['propertyValueId'])) {
                    $counter = \count($collectedData[$columnMapper['propertyValueId']]);
                } else {
                    $counter = \count($collectedData[$columnMapper['propertyValueName']]);
                }

                foreach ($collectedData as $key => $values) {
                    for ($i = 0; $i < $counter; ++$i) {
                        if ($mapper[$key] === 'propertyValueName') {
                            $value = \explode(':', $values[$i]);
                            $newData[$i][$columnMapper['propertyOptionName']] = $value[0];
                            $newData[$i][$key] = $value[1];
                        } else {
                            $newData[$i][$key] = $values[$i];
                        }
                        $newData[$i][$columnMapper['propertyGroupName']] = $propertyGroupName;
                    }
                }

                return $newData;
            } elseif ($node['adapter'] === 'translation') {
                $tempData = [];
                $translationColumns = [];
                $dataColumns = \array_keys($data);

                $columns = $this->getAllTranslationColumns();
                foreach ($columns as $column) {
                    $tempData[$column] = $this->findNodeByShopwareField($node, $column);

                    if ($tempData[$column]) {
                        $greps = \preg_grep('/^' . $tempData[$column] . '_\d+$/i', $dataColumns);
                        $translationColumns = \array_merge($translationColumns, $greps);
                    }
                }

                unset($tempData);

                $translationLang = $this->findNodeByShopwareField($node, 'languageId');

                foreach ($translationColumns as $column) {
                    \preg_match('/(?P<column>.*)_+(?P<langId>.*)$/i', $column, $matches);
                    $columnName = $matches['column'];
                    $translations[$matches['langId']][$columnName] = $data[$column];
                    if ($translationLang) {
                        $translations[$matches['langId']][$translationLang] = $matches['langId'];
                    }
                }

                return $translations;
            } elseif ($node['adapter'] != $this->getMainAdapter()) {
                $mapper = $this->createMapperFromProfile($node);

                foreach ($mapper as $key => $value) {
                    $collectedData[$key] = $this->getDataValue($data, $key);
                }

                $newData = [];
                foreach ($collectedData as $key => $groupValue) {
                    $values = \explode('|', $groupValue);
                    foreach ($values as $index => $value) {
                        $newData[$index][$key] = $value;
                    }
                }

                return $newData;
            }
        }

        if (isset($node['children'])) {
            if (isset($node['attributes'])) {
                foreach ($node['attributes'] as $attribute) {
                    $currentNode['_attributes'][$attribute['name']] = $this->getDataValue($data, $attribute['name']);
                }
            }

            foreach ($node['children'] as $child) {
                $currentPath = $nodePath . '/' . $child['name'];
                $dataValue = $this->transformToTree($child, $data, $currentPath);

                if ($dataValue !== null) {
                    $currentNode[$child['name']] = $dataValue;
                }
            }
        } else {
            if (isset($node['attributes'])) {
                foreach ($node['attributes'] as $attribute) {
                    $currentNode['_attributes'][$attribute['name']] = $this->getDataValue($data, $attribute['name']);
                }

                $currentNode['_value'] = $this->getDataValue($data, $node['name']);
            } else {
                $currentNode = $this->getDataValue($data, $node['name']);
            }
        }

        return $currentNode;
    }

    /**
     * Returns first element of the array
     *
     * @param array<string, mixed> $data
     */
    public function getFirstElement(array $data): string
    {
        if (\is_array($data)) {
            \reset($data);

            return \current($data);
        }

        return $data;
    }

    /**
     * Returns data from the CSV
     * If data don't match with the csv column names, throws exception
     *
     * @param array<string, mixed> $data
     */
    public function getDataValue(array $data, string $key): string
    {
        if (!isset($data[$key])) {
            return '';
        }

        return $data[$key];
    }

    /**
     * Creates columns name for the csv file
     *
     * @param array<string, mixed> $node
     */
    public function collectHeader(array $node, string $path): void
    {
        if ($this->iterationParts[$path] === 'price') {
            $priceProfile = $this->getNodeFromProfile($this->getMainIterationPart(), 'price');

            if (!\is_array($priceProfile)) {
                return;
            }

            $priceNodeMapper = $this->createMapperFromProfile($priceProfile);

            // saving nodes different from price groups
            foreach ($this->getCustomerGroups() as $group) {
                $this->createHeaderPriceNodes($priceNodeMapper, $group->getKey());
            }
        } elseif ($this->iterationParts[$path] === 'configurator') {
            $configuratorProfile = $this->getNodeFromProfile($this->getMainIterationPart(), 'configurator');

            if (!\is_array($configuratorProfile)) {
                return;
            }

            $configuratorNodeMapper = $this->createMapperFromProfile($configuratorProfile);

            // group name, group description and group id is skipped
            $skipList = ['configGroupId', 'configGroupName', 'configGroupDescription'];
            $this->createHeaderValues($configuratorNodeMapper, $skipList);
        } elseif ($this->iterationParts[$path] === 'propertyValue') {
            $propertyProfile = $this->getNodeFromProfile($this->getMainIterationPart(), 'propertyValue');

            if (!\is_array($propertyProfile)) {
                return;
            }

            $propertyProfileNodeMapper = $this->createMapperFromProfile($propertyProfile);

            // group name, group description and group id is skipped
            $skipList = ['propertyOptionName'];
            $this->createHeaderValues($propertyProfileNodeMapper, $skipList);
        } elseif ($this->iterationParts[$path] === 'translation') {
            $translationProfile = $this->getNodeFromProfile($this->getMainIterationPart(), 'translation');

            if (!\is_array($translationProfile)) {
                return;
            }

            $translationNodeMapper = $this->createMapperFromProfile($translationProfile);

            foreach ($this->getShops() as $shop) {
                if ((int) $shop->getId() !== 1) {
                    $this->createHeaderTranslation($translationNodeMapper, $shop->getId());
                }
            }
        } elseif ($this->iterationParts[$path] === 'taxRateSum') {
            $taxSumProfile = $this->getNodeFromProfile($this->getMainIterationPart(), 'taxRateSum');

            if (!\is_array($taxSumProfile)) {
                return;
            }

            $taxSumNodeMapper = $this->createMapperFromProfile($taxSumProfile);

            $taxRates = $this->getTaxRates();
            foreach ($taxRates as $taxRate) {
                $this->createHeaderTaxSumNodes($taxSumNodeMapper, (float) $taxRate['taxRate']);
            }
        } else {
            foreach ($node as $key => $value) {
                if (\is_array($value)) {
                    $currentPath = $path . '/' . $key;
                    $this->collectHeader($value, $currentPath);
                } else {
                    if ($key === '_value') {
                        $pathParts = \explode('/', $path);
                        $this->saveTempData($pathParts[\count($pathParts) - 1]);
                    } else {
                        $this->saveTempData($key);
                    }
                }
            }
        }
    }

    /**
     * Saves price nodes as column names
     *
     * @param array<string, mixed> $node
     */
    public function createHeaderPriceNodes(array $node, string $groupKey, string $path = null): void
    {
        foreach ($node as $key => $value) {
            if (\is_array($value)) {
                $currentPath = $this->getMergedPath($path, $key);
                $this->createHeaderPriceNodes($value, $groupKey, $currentPath);
            } else {
                if ($value === 'priceGroup') {
                    continue;
                }
                if ($key === '_value') {
                    $pathParts = \explode('/', $path);
                    $name = $pathParts[\count($pathParts) - 1] . '_' . $groupKey;
                    $this->saveTempData($name);
                } else {
                    $key .= '_' . $groupKey;
                    $this->saveTempData($key);
                }
            }
        }
    }

    /**
     * Saves tax rate sum nodes as column names
     *
     * @param array<string, mixed> $node
     */
    public function createHeaderTaxSumNodes(array $node, float $taxRate, string $path = null): void
    {
        foreach ($node as $key => $value) {
            if (\is_array($value)) {
                $currentPath = $this->getMergedPath($path, $key);
                $this->createHeaderTaxSumNodes($value, $taxRate, $currentPath);
            } else {
                if ($value === 'taxRate') {
                    continue;
                }
                if ($key === '_value') {
                    $pathParts = \explode('/', $path);
                    $name = $pathParts[\count($pathParts) - 1] . '_' . $taxRate;
                    $this->saveTempData($name);
                } else {
                    $key .= '_' . $taxRate;
                    $this->saveTempData($key);
                }
            }
        }
    }

    /**
     * Saves configurator nodes, also skipping
     * configGroupId, configGroupName and configGroupDescription
     *
     * @param array<string, mixed> $node
     * @param array<int|string>    $skipList
     */
    public function createHeaderValues(array $node, array $skipList, string $path = null): void
    {
        foreach ($node as $key => $value) {
            if (\is_array($value)) {
                $currentPath = $path . '/' . $key;
                $this->createHeaderValues($value, $skipList, $currentPath);
            } else {
                if (\in_array($value, $skipList)) {
                    continue;
                }

                if ($key === '_value') {
                    $pathParts = \explode('/', $path);
                    $name = $pathParts[\count($pathParts) - 1];
                    $this->saveTempData($name);
                } else {
                    $this->saveTempData($key);
                }
            }
        }
    }

    /**
     * Saves translation nodes and skips languageId
     *
     * @param array<string, mixed> $node
     */
    public function createHeaderTranslation(array $node, int $shopId, string $path = null): void
    {
        foreach ($node as $key => $value) {
            if (\is_array($value)) {
                $currentPath = $path . '/' . $key;
                $this->createHeaderTranslation($value, $shopId, $currentPath);
            } else {
                if ($value === 'languageId') {
                    continue;
                }

                if ($key === '_value') {
                    $pathParts = \explode('/', $path);
                    $name = $pathParts[\count($pathParts) - 1] . '_' . $shopId;
                    $this->saveTempData($name);
                } else {
                    $key .= '_' . $shopId;
                    $this->saveTempData($key);
                }
            }
        }
    }

    /**
     * Collects record data
     *
     * @param array<string|int, mixed> $node
     */
    public function collectData(array $node, string $path): void
    {
        if (isset($this->iterationParts[$path]) && $this->iterationParts[$path] != $this->getMainAdapter()) {
            if ($this->iterationParts[$path] === 'price') {
                $priceProfile = $this->getNodeFromProfile($this->getMainIterationPart(), 'price');

                if (!\is_array($priceProfile)) {
                    return;
                }

                $priceTreeMapper = $this->createMapperFromProfile($priceProfile);
                $priceFlatMapper = $this->treeToFlat($priceTreeMapper);

                // todo: check price group flag
                foreach ($this->getCustomerGroups() as $group) {
                    $priceNode = $this->findNodeByPriceGroup($node, $group->getKey(), $priceFlatMapper);

                    if ($priceNode) {
                        $this->collectPriceData($priceNode, $priceFlatMapper);
                    } else {
                        $this->collectPriceData($priceTreeMapper, $priceFlatMapper, null, true);
                    }
                    unset($priceNode);
                }
            } elseif ($this->iterationParts[$path] === 'configurator') {
                $configuratorProfile = $this->getNodeFromProfile($this->getMainIterationPart(), 'configurator');

                if (!\is_array($configuratorProfile)) {
                    return;
                }

                $configuratorTreeMapper = $this->createMapperFromProfile($configuratorProfile);
                $configuratorFlatMapper = $this->treeToFlat($configuratorTreeMapper);

                foreach ($node as $key => $configurator) {
                    $this->collectConfiguratorData($configurator, $configuratorFlatMapper, null, $configurator);
                }

                $iterationTempData = $this->getIterationTempData();

                foreach ($iterationTempData as $key => $tempData) {
                    if (\is_array($tempData)) {
                        if ($configuratorFlatMapper[$key] === 'configSetId'
                            || $configuratorFlatMapper[$key] === 'configSetType'
                            || $configuratorFlatMapper[$key] === 'configSetName'
                        ) {
                            $this->saveTempData((string) $tempData[0]);
                        } else {
                            $data = \implode('|', $tempData);
                            $this->saveTempData($data);
                        }
                    }
                }

                unset($this->iterationTempData);
            } elseif ($this->iterationParts[$path] === 'propertyValue') {
                $propertyValueProfile = $this->getNodeFromProfile($this->getMainIterationPart(), 'propertyValue');

                if (!\is_array($propertyValueProfile)) {
                    return;
                }

                $propertyValueTreeMapper = $this->createMapperFromProfile($propertyValueProfile);
                $propertyValueFlatMapper = $this->treeToFlat($propertyValueTreeMapper);

                foreach ($node as $key => $property) {
                    $this->collectPropertyData($property, $propertyValueFlatMapper, null, $property);
                }

                $iterationTempData = $this->getIterationTempData();

                foreach ($iterationTempData as $key => $tempData) {
                    if (\is_array($tempData)) {
                        if ($propertyValueFlatMapper[$key] === 'propertyGroupName') {
                            $this->saveTempData((string) $tempData[0]);
                        } else {
                            $data = \implode('|', $tempData);
                            $this->saveTempData($data);
                        }
                    }
                }

                unset($this->iterationTempData);
            } elseif ($this->iterationParts[$path] === 'translation') {
                $translationProfile = $this->getNodeFromProfile($this->getMainIterationPart(), 'translation');

                if (!\is_array($translationProfile)) {
                    return;
                }

                $translationTreeMapper = $this->createMapperFromProfile($translationProfile);
                $translationFlatMapper = $this->treeToFlat($translationTreeMapper);

                foreach ($node as $key => $translation) {
                    $this->collectTranslationData($translation, $translationFlatMapper);
                }
            } elseif ($this->iterationParts[$path] === 'translationProperty') {
                $translationPProfile = $this->getNodeFromProfile($this->getMainIterationPart(), 'translationProperty');

                if (!\is_array($translationPProfile)) {
                    return;
                }

                $translationPTreeMapper = $this->createMapperFromProfile($translationPProfile);
                $translationPFlatMapper = $this->treeToFlat($translationPTreeMapper);

                foreach ($node as $value) {
                    $this->collectIterationData($value);
                }

                foreach ($this->getIterationTempData() as $nodeName => $tempData) {
                    if ($translationPFlatMapper[$nodeName] === 'propertyGroupBaseName'
                        || $translationPFlatMapper[$nodeName] === 'propertyGroupName'
                        || $translationPFlatMapper[$nodeName] === 'propertyGroupId'
                    ) {
                        $this->saveTempData((string) $tempData[0]);
                    } elseif (\is_array($tempData)) {
                        $data = \implode('|', $tempData);
                        $this->saveTempData($data);
                    }
                }
                unset($this->iterationTempData);
            } elseif ($this->iterationParts[$path] === 'taxRateSum') {
                $taxSumProfile = $this->getNodeFromProfile($this->getMainIterationPart(), 'taxRateSum');

                if (!\is_array($taxSumProfile)) {
                    return;
                }

                $taxSumTreeMapper = $this->createMapperFromProfile($taxSumProfile);
                $taxSumFlatMapper = $this->treeToFlat($taxSumTreeMapper);

                $taxRates = $this->getTaxRates();
                foreach ($taxRates as $taxRate) {
                    $taxRateNode = $this->findNodeByTaxRate($node, (float) $taxRate['taxRate'], $taxSumFlatMapper);
                    if ($taxRateNode) {
                        $this->collectTaxRateData($taxRateNode, $taxSumFlatMapper);
                    } else {
                        $this->collectTaxRateData($taxSumTreeMapper, $taxSumFlatMapper, null, true);
                    }
                    unset($taxRateNode);
                }
            } else {
                // processing images, similar and accessories
                foreach ($node as $value) {
                    $this->collectIterationData($value);
                }

                foreach ($this->getIterationTempData() as $tempData) {
                    if (\is_array($tempData)) {
                        $data = \implode('|', $tempData);
                        $this->saveTempData($data);
                    }
                }

                unset($this->iterationTempData);
            }
        } else {
            foreach ($node as $key => $value) {
                if (\is_array($value)) {
                    $currentPath = $this->getMergedPath($path, (string) $key);
                    $this->collectData($value, $currentPath);
                } else {
                    $this->saveTempData((string) $value);
                }
            }
        }
    }

    /**
     * @param array<string, mixed> $node
     */
    public function collectIterationData(array $node, ?string $path = null): void
    {
        foreach ($node as $key => $value) {
            $currentPath = $this->getMergedPath($path, (string) $key);

            if (\is_array($value)) {
                $this->collectIterationData($value, $currentPath);
            } else {
                $this->saveIterationTempData($currentPath, $value);
            }
        }
    }

    /**
     * Returns price node by price group
     *
     * @param array<string|int, mixed> $node
     *
     * @return array
     */
    public function findNodeByPriceGroup(array $node, string $groupKey, array $mapper): ?array
    {
        foreach ($node as $value) {
            $priceKey = $this->getPriceGroupFromNode($value, $mapper);
            if ($priceKey == $groupKey) {
                return $value;
            }
        }

        return null;
    }

    /**
     * @param array<string, mixed> $node
     * @param array<string, mixed> $mapper
     */
    public function getPriceGroupFromNode(array $node, array $mapper, string $path = null): ?string
    {
        foreach ($node as $key => $value) {
            $currentPath = $this->getMergedPath($path, (string) $key);

            if (\is_array($value)) {
                $result = $this->getPriceGroupFromNode($value, $mapper, $currentPath);

                if ($result) {
                    return $result;
                }
            }

            if ($mapper[$currentPath] === 'priceGroup') {
                return $value;
            }
        }

        return null;
    }

    /**
     * @param array<string, mixed> $node
     * @param array<string, mixed> $mapper
     */
    public function collectPriceData(array $node, array $mapper, string $path = null, bool $emptyResult = false): void
    {
        foreach ($node as $key => $value) {
            $currentPath = $this->getMergedPath($path, $key);

            if (\is_array($value)) {
                $this->collectPriceData($value, $mapper, $currentPath, $emptyResult);
            } else {
                if ($mapper[$currentPath] !== 'priceGroup') {
                    if ($emptyResult) {
                        $this->saveTempData(null);
                    } else {
                        $this->saveTempData((string) $value);
                    }
                }
            }
        }
    }

    /**
     * @param array<string, mixed> $node
     *
     * @return mixed[]
     */
    public function treeToFlat(array $node): array
    {
        $this->resetTempMapper();
        $this->convertToFlat($node, null);

        return $this->getTempMapper();
    }

    /**
     * Returns the iteration part of the tree
     */
    public function getMainIterationPart(): array
    {
        if ($this->mainIterationPart == null) {
            $tree = \json_decode($this->config, true);
            $this->findMainIterationPart($tree);
        }

        return $this->mainIterationPart;
    }

    public function getMainAdapter(): string
    {
        if ($this->mainAdapter == null) {
            $mainIterationPart = $this->getMainIterationPart();
            $this->mainAdapter = $mainIterationPart['adapter'];
        }

        return $this->mainAdapter;
    }

    /**
     * Finds and saves iteration parts
     *
     * @param array<string, mixed> $nodes
     */
    public function processIterationParts(array $nodes, string $path = null): void
    {
        foreach ($nodes as $key => $node) {
            if (isset($nodes['name'])) {
                $currentPath = $this->getMergedPath($path, $nodes['name']);
            } else {
                $currentPath = $path;
            }

            if ($key === 'type' && $node === 'iteration') {
                $this->saveIterationParts($currentPath, $nodes['adapter']);
            }

            if (\is_array($node)) {
                $this->processIterationParts($node, $currentPath);
            }
        }
    }

    public function saveIterationTempData(string $currentPath, $value): void
    {
        $this->iterationTempData[$currentPath][] = $value;
    }

    public function getIterationTempData(): array
    {
        return $this->iterationTempData;
    }

    public function unsetIterationTempData(): void
    {
        unset($this->iterationTempData);
    }

    /**
     * @param array<string, mixed> $node
     *
     * @return array<string, mixed>|null
     */
    public function getNodeFromProfile(array $node, string $adapter): ?array
    {
        foreach ($node as $key => $value) {
            if ($key === 'adapter' && $value == $adapter) {
                return $node;
            }

            if (\is_array($value)) {
                $seekNode = $this->getNodeFromProfile($value, $adapter);
                if ($seekNode) {
                    return $seekNode;
                }
            }
        }

        return null;
    }

    /**
     * Returns configuration group value by given node and mapper
     *
     * @param array<string|int, mixed> $node
     * @param array<string, string>    $mapper
     * @param string                   $path
     *
     * @return string
     */
    public function findConfigurationGroupValue(array $node, array $mapper, string $path = null): ?string
    {
        foreach ($node as $key => $value) {
            $currentPath = $this->getMergedPath($path, (string) $key);

            if (\is_array($value)) {
                $result = $this->findConfigurationGroupValue($value, $mapper, $currentPath);

                if ($result) {
                    return $result;
                }
            } else {
                if ($mapper[$currentPath] === 'configGroupName') {
                    return $value;
                }
            }
        }

        return null;
    }

    /**
     * @param array<string|int, mixed> $node
     * @param array<string, string>    $mapper
     */
    public function findPropertyOptionName(array $node, array $mapper, ?string $path = null): ?string
    {
        foreach ($node as $key => $value) {
            $currentPath = $this->getMergedPath($path, (string) $key);

            if (\is_array($value)) {
                $result = $this->findPropertyOptionName($value, $mapper, $currentPath);

                if ($result) {
                    return $result;
                }
            } else {
                if ($mapper[$currentPath] === 'propertyOptionName') {
                    return $value;
                }
            }
        }

        return null;
    }

    /**
     * @param array<string|int, mixed>  $node
     * @param array<string, string>     $mapper
     * @param ?array<string|int, mixed> $originalNode
     */
    public function collectConfiguratorData(array $node, array $mapper, string $path = null, array $originalNode = null): void
    {
        foreach ($node as $key => $value) {
            $currentPath = $this->getMergedPath($path, (string) $key);

            if (\is_array($value)) {
                $this->collectConfiguratorData($value, $mapper, $currentPath, $node);
            } else {
                if ($mapper[$currentPath] === 'configGroupName'
                    || $mapper[$currentPath] === 'configGroupDescription'
                    || $mapper[$currentPath] === 'configGroupId'
                ) {
                    continue;
                }

                if ($mapper[$currentPath] === 'configOptionName') {
                    $group = $this->findConfigurationGroupValue($originalNode ?? [], $mapper);

                    // check if configuration group and value are not empty or string 0
                    if ((!empty($value) || $value == '0') && !empty($group)) {
                        $mixedValue = $group . ':' . $value;
                    }

                    $this->saveIterationTempData($currentPath, $mixedValue);
                    unset($mixedValue);
                } else {
                    $this->saveIterationTempData($currentPath, $value);
                }
            }
        }
    }

    /**
     * @param array<string|int, mixed>  $node
     * @param array<string, string>     $mapper
     * @param ?array<string|int, mixed> $originalNode
     */
    public function collectPropertyData(array $node, array $mapper, string $path = null, array $originalNode = null): void
    {
        foreach ($node as $key => $value) {
            $currentPath = $this->getMergedPath($path, (string) $key);

            if (\is_array($value)) {
                $this->collectPropertyData($value, $mapper, $currentPath, $node);
            } else {
                if ($mapper[$currentPath] === 'propertyOptionName') {
                    continue;
                }

                if ($mapper[$currentPath] === 'propertyValueName') {
                    $option = $this->findPropertyOptionName($originalNode ?? [], $mapper);

                    if ($value && $option) {
                        $mixedValue = $option . ':' . $value;
                    }

                    $this->saveIterationTempData($currentPath, $mixedValue);
                    unset($mixedValue);
                } else {
                    $this->saveIterationTempData($currentPath, $value);
                }
            }
        }
    }

    /**
     * @param array<string|int, mixed> $node
     * @param array<string, string>    $mapper
     */
    public function collectTranslationData(array $node, array $mapper, string $path = null): void
    {
        foreach ($node as $key => $value) {
            $currentPath = $this->getMergedPath($path, (string) $key);
            if (\is_array($value)) {
                $this->collectTranslationData($value, $mapper, $currentPath);
            } else {
                if ($mapper[$currentPath] === 'languageId') {
                    continue;
                }

                $this->saveTempData((string) $value);
            }
        }
    }

    public function getMergedPath(?string $path, string $key): string
    {
        if ($path) {
            $newPath = $path . '/' . $key;
        } else {
            $newPath = $key;
        }

        return $newPath;
    }

    /**
     * Saves iteration parts
     */
    public function saveIterationParts(string $path, string $adapter): void
    {
        $this->iterationParts[$path] = $adapter;
    }

    public function resetTempData(): void
    {
        $this->tempData = [];
    }

    /**
     * @return array<string>
     */
    public function getTempData(): array
    {
        return $this->tempData;
    }

    public function saveTempData(?string $data): void
    {
        $this->tempData[] = $data;
    }

    public function resetTempMapper(): void
    {
        $this->tempMapper = [];
    }

    /**
     * @return array<int|string, mixed>
     */
    public function getTempMapper(): array
    {
        return $this->tempMapper;
    }

    public function saveTempMapper(string $path, string $data): void
    {
        $this->tempMapper[$path] = $data;
    }

    /**
     * @return Group[]
     */
    public function getCustomerGroups(): array
    {
        return Shopware()->Models()->getRepository(Group::class)->findAll();
    }

    /**
     * @return Shop[]
     */
    public function getShops(): array
    {
        return Shopware()->Models()->getRepository(Shop::class)->findAll();
    }

    /**
     * @return array<string>
     */
    public function getAllTranslationColumns(): array
    {
        if ($this->translationColumns === null) {
            $translationFields = [
                'name',
                'keywords',
                'metaTitle',
                'description',
                'descriptionLong',
                'additionalText',
                'packUnit',
                'shippingTime',
            ];

            $attributes = $this->getAttributeColumns();

            $this->translationColumns = \array_merge($translationFields, $attributes);
        }

        return $this->translationColumns;
    }

    /**
     * @throws \Zend_Db_Statement_Exception
     *
     * @return array<array<string>>
     */
    public function getTaxRates(): array
    {
        $sql = '(SELECT tax as taxRate FROM s_core_tax)
                UNION
                (SELECT tax_rate as taxRate FROM s_order_details)
                UNION
                (SELECT tax as taxRate FROM s_core_tax_rules)
                ORDER BY taxRate ASC';

        return Shopware()->Db()->query($sql)->fetchAll();
    }

    /**
     * Returns price node by price group
     *
     * @param array<string|int, mixed> $node
     * @param array<string, string>    $mapper
     *
     * @return array
     */
    public function findNodeByTaxRate(array $node, float $taxRate, array $mapper): ?array
    {
        foreach ($node as $value) {
            $rate = $this->getTaxRateFromNode($value, $mapper);
            if ($rate == $taxRate) {
                return $value;
            }
        }

        return null;
    }

    /**
     * @param array<string|int, mixed> $node
     * @param array<string, string>    $mapper
     */
    public function getTaxRateFromNode(array $node, array $mapper, string $path = null): ?float
    {
        foreach ($node as $key => $value) {
            $currentPath = $this->getMergedPath($path, (string) $key);
            if (\is_array($value)) {
                $result = $this->getTaxRateFromNode($value, $mapper, $currentPath);

                if ($result) {
                    return $result;
                }
            }

            if ($mapper[$currentPath] === 'taxRate') {
                return (float) $value;
            }
        }

        return null;
    }

    /**
     * @param array<string|int, mixed> $node
     * @param array<string, string>    $mapper
     */
    public function collectTaxRateData(array $node, array $mapper, string $path = null, bool $emptyResult = false): void
    {
        foreach ($node as $key => $value) {
            $currentPath = $this->getMergedPath($path, (string) $key);
            if (\is_array($value)) {
                $this->collectTaxRateData($value, $mapper, $currentPath, $emptyResult);
            } elseif ($mapper[$currentPath] === 'taxRateSums') {
                $tempData = ($emptyResult === true) ? null : $value;
                $this->saveTempData((string) $tempData);
            }
        }
    }

    /**
     * Returns the columns of the article attributes table
     */
    protected function getAttributeColumns(): array
    {
        /** @var AbstractSchemaManager $schemaManager */
        $schemaManager = Shopware()->Container()->get('models')->getConnection()->getSchemaManager();

        return \array_keys($schemaManager->listTableColumns('s_articles_attributes'));
    }

    /**
     * Finds the name of column with price field
     *
     * @param array<string, mixed> $node
     */
    protected function findNodeByShopwareField(array $node, string $shopwareField): ?string
    {
        if (isset($node['shopwareField']) && $node['shopwareField'] == $shopwareField) {
            return $node['name'];
        }
        if (isset($node['children'])) {
            foreach ($node['children'] as $child) {
                $return = $this->findNodeByShopwareField($child, $shopwareField);
                if (\is_string($return)) {
                    return $return;
                }
            }
        }
        if (isset($node['attributes'])) {
            foreach ($node['attributes'] as $attribute) {
                $return = $this->findNodeByShopwareField($attribute, $shopwareField);
                if (\is_string($return)) {
                    return $return;
                }
            }
        }

        return null;
    }

    /**
     * Transform flat price data into tree array
     *
     * @param array<string, mixed> $node
     * @param array<string, mixed> $data
     */
    protected function transformPricesToTree(array $node, array $data, string $group)
    {
        // special case for EK group ('_EK' may be missing)
        if ($group != '') {
            $groupValue = $group;
            $groupExtension = '_' . $group;
        } else {
            $groupValue = 'EK';
            $groupExtension = '';
        }

        if (isset($node['children'])) {
            if (isset($node['attributes'])) {
                foreach ($node['attributes'] as $attribute) {
                    if ($attribute['shopwareField'] !== 'priceGroup') {
                        $value = $this->getDataValue($data, $attribute['name'] . $groupExtension);
                    } else {
                        $value = $groupValue;
                    }
                    $currentNode['_attributes'][$attribute['name']] = $value;
                }
            }

            // the check for group value is not done here, but on the next level (recursion)
            // because the node may have attribute(s)
            foreach ($node['children'] as $child) {
                $currentNode[$child['name']] = $this->transformPricesToTree($child, $data, $group);
            }
        } else {
            if (isset($node['attributes'])) {
                foreach ($node['attributes'] as $attribute) {
                    if ($attribute['shopwareField'] !== 'priceGroup') {
                        $value = $this->getDataValue($data, $attribute['name'] . $groupExtension);
                    } else {
                        $value = $groupValue;
                    }
                    $currentNode['_attributes'][$attribute['name']] = $value;
                }

                if ($node['shopwareField'] !== 'priceGroup') {
                    $value = $this->getDataValue($data, $node['name'] . $groupExtension);
                } else {
                    $value = $groupValue;
                }
                $currentNode['_value'] = $value;
            } else {
                if ($node['shopwareField'] !== 'priceGroup') {
                    $value = $this->getDataValue($data, $node['name'] . $groupExtension);
                } else {
                    $value = $groupValue;
                }
                $currentNode = $value;
            }
        }

        return $currentNode;
    }

    /**
     * Transform flat configurator data into tree array
     *
     * @param array<string, mixed>     $node
     * @param array<int|string, mixed> $data
     */
    protected function transformConfiguratorToTree(array $node, array $data)
    {
        if (isset($node['children'])) {
            if (isset($node['attributes'])) {
                foreach ($node['attributes'] as $attribute) {
                    if (isset($data[$attribute['name']])) {
                        $value = $data[$attribute['name']];
                    } else {
                        $value = '';
                    }
                    $currentNode['_attributes'][$attribute['name']] = $value;
                }
            }

            // the check for group value is not done here, but on the next level (recursion)
            // because the node may have attribute(s)
            foreach ($node['children'] as $child) {
                $currentNode[$child['name']] = $this->transformConfiguratorToTree($child, $data);
            }
        } else {
            if (isset($node['attributes'])) {
                foreach ($node['attributes'] as $attribute) {
                    if (isset($data[$attribute['name']])) {
                        $value = $data[$attribute['name']];
                    } else {
                        $value = '';
                    }
                    $currentNode['_attributes'][$attribute['name']] = $value;
                }

                if (isset($data[$node['name']])) {
                    $value = $data[$node['name']];
                } else {
                    $value = '';
                }
                $currentNode['_value'] = $value;
            } else {
                if (isset($data[$node['name']])) {
                    $value = $data[$node['name']];
                } else {
                    $value = '';
                }
                $currentNode = $value;
            }
        }

        return $currentNode;
    }

    /**
     * @param array<string|int, mixed> $node
     */
    protected function convertToFlat(array $node, ?string $path): void
    {
        foreach ($node as $key => $value) {
            $currentPath = $this->getMergedPath($path, (string) $key);

            if (\is_array($value)) {
                $this->convertToFlat($value, $currentPath);
            } else {
                $this->saveTempMapper($currentPath, $value);
            }
        }
    }
}
