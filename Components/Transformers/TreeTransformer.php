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

/**
 * The responsibility of this class is to restructure the flat array to tree and vise versa
 */
class TreeTransformer implements DataTransformerAdapter, ComposerInterface
{
    public const TYPE = 'tree';

    private ?string $config = null;

    private ?array $iterationPart = null;

    private ?array $headerFooterData = null;

    private ?string $mainType = null;

    /**
     * @var array<string, array<int, mixed>>
     */
    private array $rawData = [];

    /**
     * @var array<string, array<string, string|null>>|null
     */
    private ?array $bufferData = null;

    /**
     * @var array<string, mixed>|null
     */
    private ?array $importMapper = null;

    /**
     * @var array<string, array<string, mixed>>|null
     */
    private ?array $data = null;

    /**
     * @var array<string, string>|null
     */
    private ?array $currentRecord = null;

    /**
     * @var array<string, array<string, array<array-key, mixed>>>|null
     */
    private ?array $preparedData = null;

    /**
     * @var array<string, mixed>
     */
    private array $iterationNodes = [];

    private \Enlight_Event_EventManager $eventManager;

    public function __construct(
        \Enlight_Event_EventManager $eventManager
    ) {
        $this->eventManager = $eventManager;
    }

    public function supports(string $type): bool
    {
        return $type === self::TYPE;
    }

    public function initialize(Profile $profile): void
    {
        $this->config = $profile->getEntity()->getTree();
        $this->reset();
    }

    /**
     * Transforms the flat array into tree with list of nodes containing children and attributes.
     *
     * @param array<string, array<mixed>> $data
     *
     * @return array<int|string, array<int, mixed>>
     */
    public function transformForward(array $data): array
    {
        $this->reset();
        $this->setData($data);
        $transformData = [];

        $iterationPart = $this->getIterationPart();

        $adapter = $iterationPart['adapter'];

        foreach ($data[$adapter] as $record) {
            $this->currentRecord = $record;
            $transformData[] = $this->transformToTree($iterationPart, $record, $adapter);
            unset($this->currentRecord);
        }

        // creates iteration array
        $tree = [$iterationPart['name'] => $transformData];

        return $this->eventManager->filter(
            'Shopware_Components_SwagImportExport_Transformers_TreeTransformer_TransformForward',
            $tree,
            ['subject' => $this]
        );
    }

    /**
     * Transforms a list of nodes containing children and attributes into flat array.
     *
     * @param array<string, array<mixed>> $data
     */
    public function transformBackward(array $data): array
    {
        $data = $this->eventManager->filter(
            'Shopware_Components_SwagImportExport_Transformers_TreeTransformer_TransformBackward',
            $data,
            ['subject' => $this]
        );

        // gets iteration nodes
        $this->getIterationNodes();

        $iterationPart = $this->getIterationPart();
        $this->mainType = $iterationPart['adapter'];

        $this->buildRawData($data, $iterationPart['adapter']);

        return $this->getRawData();
    }

    /**
     * Composes a tree header based on config
     */
    public function composeHeader(): array
    {
        return $this->getHeaderAndFooterData();
    }

    /**
     * Composes a tree footer based on config
     */
    public function composeFooter(): array
    {
        return $this->getHeaderAndFooterData();
    }

    /**
     * Helper method which creates iteration nodes array structure
     *
     * @param array<string, mixed> $node
     */
    private function buildIterationNode(array $node): array
    {
        $transformData = [];

        if ($this->currentRecord === null) {
            throw new \Exception('Current record was not found');
        }

        if (!isset($node['adapter'])) {
            throw new \Exception('Adapter was not found');
        }

        if (!isset($node['parentKey'])) {
            throw new \Exception('Parent key was not found');
        }

        $type = $node['adapter'];
        $parentKey = $node['parentKey'];

        // gets connections between iteration nodes
        $recordLink = $this->currentRecord[$parentKey];

        // prepares raw data
        $data = $this->getPreparedData($type, $parentKey);

        // gets records for the current iteration node
        $records = $data[$recordLink] ?? [];

        if (empty($records)) {
            $transformData[] = $this->transformToTree($node, [], $type);
        } else {
            foreach ($records as $record) {
                $transformData[] = $this->transformToTree($node, $record, $type);
            }
        }

        return $transformData;
    }

    /**
     * Helper method which creates rawData from array structure
     *
     * @param array<string, array<mixed>> $data
     */
    private function buildRawData(array $data, string $type, ?string $nodePath = null): void
    {
        // creates import mapper
        // ["Prices_Price_groupName"]=> "pricegroup"
        $importMapper = $this->getImportMapper();

        foreach ($data as $record) {
            $this->transformFromTree($record, $importMapper, $type, $nodePath);
            $bufferData = $this->getBufferData($type);

            if ($this->getMainType() !== $type) {
                $rawData = $this->getRawData();
                if (isset($rawData[$this->getMainType()])) {
                    $parentElement = \count($rawData[$this->getMainType()]);
                } else {
                    $parentElement = 0;
                }
                $bufferData['parentIndexElement'] = $parentElement;
            }

            $this->setRawData($type, $bufferData);
            $this->unsetBufferData($type);
        }
    }

    /**
     * Get iteration nodes from profile
     */
    private function getIterationNodes(): void
    {
        $iterationPart = $this->getIterationPart();

        $this->createIterationNodeMapper($iterationPart);
    }

    /**
     * Transforms read it data into raw data
     *
     * @param array<string, mixed>|string|null $node
     * @param array<string, string>            $importMapper
     */
    private function transformFromTree($node, array $importMapper, string $adapter, ?string $nodePath = null): void
    {
        $separator = null;

        if ($nodePath) {
            $separator = '_';
        }

        if (isset($this->iterationNodes[$nodePath]) && $adapter !== $this->iterationNodes[$nodePath]) {
            if (!\is_array($node)) {
                throw new \RuntimeException('Parameter "node" must be an array at this point');
            }
            $this->buildRawData($node, $this->iterationNodes[$nodePath], $nodePath);

            return;
        }

        if (\is_array($node) && isset($node['_value'])) {
            if (isset($importMapper[$nodePath])) {
                $this->saveBufferData($adapter, $importMapper[$nodePath], $node['_value']);
            }

            if (isset($node['_attributes'])) {
                $this->transformFromTree($node['_attributes'], $importMapper, $adapter, $nodePath);
            }
        } elseif (\is_array($node)) {
            foreach ($node as $key => $child) {
                if ($key !== '_attributes') {
                    $currentNode = $nodePath . $separator . $key;
                } else {
                    $currentNode = $nodePath;
                }

                $this->transformFromTree($child, $importMapper, $adapter, $currentNode);
            }
        } else {
            if (isset($importMapper[$nodePath])) {
                $this->saveBufferData($adapter, $importMapper[$nodePath], $node);
            }
        }
    }

    /**
     * Search the iteration part of the tree template
     *
     * @param array<string, mixed> $tree
     */
    private function findIterationPart(array $tree): void
    {
        foreach ($tree as $key => $value) {
            if ($key === 'adapter') {
                $this->iterationPart = $tree;

                return;
            }

            if (\is_array($value)) {
                $this->findIterationPart($value);
            }
        }
    }

    /**
     * Returns the iteration part of the tree
     */
    private function getIterationPart(): array
    {
        if (!\is_array($this->iterationPart)) {
            if (!\is_string($this->config)) {
                throw new \RuntimeException('Tree config not initialized');
            }
            $tree = \json_decode($this->config, true);
            $this->findIterationPart($tree);
        }

        if (!\is_array($this->iterationPart)) {
            throw new \DomainException('Iteration part is no array');
        }

        return $this->iterationPart;
    }

    /**
     * @throws \RuntimeException
     */
    private function getHeaderAndFooterData(): array
    {
        if (!\is_array($this->headerFooterData)) {
            if (!\is_string($this->config)) {
                throw new \RuntimeException('Tree config not initialized');
            }
            $tree = \json_decode($this->config, true);
            // replacing iteration part with custom marker
            $this->removeIterationPart($tree);

            $modifiedTree = $this->transformToTree($tree);

            if (!isset($tree['name'])) {
                throw new \RuntimeException('Root category in the tree does not exist');
            }

            $this->headerFooterData = [$tree['name'] => $modifiedTree];
        }

        return $this->headerFooterData;
    }

    /**
     * Returns import mapper
     */
    private function getImportMapper(): array
    {
        if ($this->importMapper === null) {
            $iterationPart = $this->getIterationPart();
            $this->generateMapper($iterationPart);
        }

        if (!\is_array($this->importMapper)) {
            throw new \DomainException('Iteration part is no array');
        }

        return $this->importMapper;
    }

    /**
     * @throws \Exception
     */
    private function saveMapper(string $key, string $value): void
    {
        $this->importMapper[$key] = $value;
    }

    private function saveBufferData(string $adapter, string $key, ?string $value): void
    {
        $this->bufferData[$adapter][$key] = $value;
    }

    /**
     * @return array<string, array<mixed>>
     */
    private function getRawData(): array
    {
        return $this->rawData;
    }

    private function setRawData(string $type, ?array $rawData): void
    {
        $this->rawData[$type][] = $rawData;
    }

    /**
     * @return array<string, string|null>|null
     */
    private function getBufferData(string $type): ?array
    {
        return $this->bufferData[$type] ?? null;
    }

    private function unsetBufferData(string $type): void
    {
        unset($this->bufferData[$type]);
    }

    /**
     * @return array<string, array<string, mixed>>|null
     */
    private function getData(): ?array
    {
        return $this->data;
    }

    /**
     * @param array<string, array<mixed>> $data
     */
    private function setData(array $data): void
    {
        $this->data = $data;
    }

    private function getMainType(): ?string
    {
        return $this->mainType;
    }

    /**
     * @return array<string, array<array-key, mixed>>
     */
    private function getPreparedData(string $type, string $recordLink): array
    {
        if (isset($this->preparedData[$type])) {
            return $this->preparedData[$type];
        }

        $data = $this->getData() ?? [];

        foreach ($data[$type] as $record) {
            $this->preparedData[$type][(string) $record[$recordLink]][] = $record;
        }

        return $this->preparedData[$type] ?? [];
    }

    /**
     * Create iteration node mapper for import
     *
     * @param array<string, mixed> $node
     */
    private function createIterationNodeMapper(array $node, ?string $nodePath = null): void
    {
        $separator = $nodePath ? '_' : null;
        if (isset($node['adapter']) && $nodePath) {
            $this->iterationNodes[$nodePath] = $node['adapter'];
        }

        if (isset($node['children'])) {
            foreach ($node['children'] as $child) {
                $currentPath = $nodePath . $separator . $child['name'];
                $this->createIterationNodeMapper($child, $currentPath);
            }
        }
    }

    /**
     * Generates import mapper from the js tree
     *
     * @param array<string, mixed> $node
     */
    private function generateMapper(array $node, ?string $nodePath = null): void
    {
        $separator = null;

        if ($nodePath) {
            $separator = '_';
        }

        if (isset($node['children'])) {
            if (isset($node['attributes']) && \is_array($node['attributes'])) {
                foreach ($node['attributes'] as $attr) {
                    $currentPath = $nodePath . $separator . $attr['name'];
                    $this->saveMapper($currentPath, $attr['shopwareField']);
                }
            }

            foreach ($node['children'] as $child) {
                $currentPath = $nodePath . $separator . $child['name'];
                $this->generateMapper($child, $currentPath);
            }
        } else {
            if (isset($node['attributes'])) {
                foreach ($node['attributes'] as $attr) {
                    $currentPath = $nodePath . $separator . $attr['name'];
                    $this->saveMapper($currentPath, $attr['shopwareField']);
                }
            }

            $this->saveMapper((string) $nodePath, $node['shopwareField']);
        }
    }

    /**
     * Replace the iteration part with custom tag "_currentMarker"
     *
     * @param array<string, mixed> $node
     */
    private function removeIterationPart(array &$node): void
    {
        if (isset($node['adapter'])) {
            $node = ['name' => '_currentMarker'];
        }

        if (isset($node['children'])) {
            foreach ($node['children'] as &$child) {
                $this->removeIterationPart($child);
            }
        }
    }

    /**
     * Preparing/Modifying nodes to converting into xml
     * and puts db data into this formatted array
     *
     * @param array<string, string> $mapper
     * @param array<string, mixed>  $node
     *
     * @return array<array<mixed>|string>|string
     */
    private function transformToTree(array $node, array $mapper = [], string $adapterType = null)
    {
        if (isset($node['adapter']) && $node['adapter'] !== $adapterType) {
            return $this->buildIterationNode($node);
        }

        $currentNode = [];

        if (!isset($node['rawKey']) && isset($node['children']) && \count($node['children']) > 0) {
            if (isset($node['attributes']) && \is_array($node['attributes'])) {
                foreach ($node['attributes'] as $attribute) {
                    $currentNode['_attributes'][$attribute['name']] = $mapper[$attribute['shopwareField']];
                }
            }

            foreach ($node['children'] as $child) {
                $currentNode[$child['name']] = $this->transformToTree($child, $mapper, $node['adapter'] ?? null);
            }
        } else {
            if (isset($node['attributes'])) {
                foreach ($node['attributes'] as $attribute) {
                    $currentNode['_attributes'][$attribute['name']] = $mapper[$attribute['shopwareField']];
                }

                $currentNode['_value'] = $mapper[$node['shopwareField']];
            } else {
                $currentNode = $mapper[$node['shopwareField'] ?? ''] ?? '';
                if (($node['type'] ?? '') === 'raw') {
                    $currentNode = $mapper[$node['rawKey']] ?? [];

                    if (isset($node['children']) && \count($node['children']) > 0) {
                        foreach ($node['children'] as $child) {
                            if ($this->isAssociativeArray($currentNode)) {
                                $currentNode[$child['name']] = $this->transformToTree($child, $currentNode, $node['adapter'] ?? null);
                            } else {
                                foreach ($currentNode as $key => $currentNodeItem) {
                                    $currentNode[$key][$child['name']] = $this->transformToTree($child, $currentNodeItem, $node['adapter'] ?? null);
                                }
                            }
                        }
                    }
                }
            }
        }

        return $currentNode;
    }

    private function reset(): void
    {
        $this->iterationPart = null;
        $this->headerFooterData = null;
        $this->mainType = null;
        $this->rawData = [];
        $this->bufferData = null;
        $this->importMapper = null;
        $this->data = null;
        $this->currentRecord = null;
        $this->preparedData = null;
        $this->iterationNodes = [];
    }

    /**
     * @param array<mixed> $arrayToCheck
     */
    private function isAssociativeArray(array $arrayToCheck): bool
    {
        return \array_keys($arrayToCheck) !== \range(0, \count($arrayToCheck) - 1);
    }
}
