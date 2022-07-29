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

    protected ?string $config = null;

    protected ?array $iterationPart = null;

    protected ?array $headerFooterData = null;

    protected ?string $mainType = null;

    /**
     * @var array<string, array<int, mixed>>
     */
    protected array $rawData = [];

    /**
     * @var array<int|string, string>
     */
    protected ?array $bufferData = null;

    /**
     * @var array<string, mixed>
     */
    protected ?array $importMapper = null;

    /**
     * @var array<string, mixed>|null
     */
    protected ?array $data = null;

    /**
     * @var array<string, string>
     */
    protected ?array $currentRecord = null;

    /**
     * @var array<string|int, string|array<mixed>
     */
    protected ?array $preparedData = null;

    /**
     * @var array<string, mixed>
     */
    protected array $iterationNodes = [];

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
     * Helper method which creates iteration nodes array structure
     *
     * @param array<string, mixed> $node
     */
    public function buildIterationNode(array $node): array
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
        $records = $data[$recordLink];

        if (!$records) {
            $transformData[] = $this->transformToTree($node, null, $type);
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
    public function buildRawData(array $data, string $type, ?string $nodePath = null): void
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
    public function getIterationNodes(): void
    {
        $iterationPart = $this->getIterationPart();

        $this->createIterationNodeMapper($iterationPart);
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
     * Transforms read it data into raw data
     *
     * @param array<string, string> $importMapper
     */
    public function transformFromTree($node, array $importMapper, string $adapter, ?string $nodePath = null): void
    {
        $separator = null;

        if ($nodePath) {
            $separator = '_';
        }

        if (isset($this->iterationNodes[$nodePath]) && $adapter !== $this->iterationNodes[$nodePath]) {
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
    public function findIterationPart(array $tree): void
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
    public function getIterationPart(): array
    {
        if (!\is_array($this->iterationPart)) {
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
    public function getHeaderAndFooterData(): ?array
    {
        if (!\is_array($this->headerFooterData)) {
            $tree = \json_decode($this->config, true);
            // replacing iteration part with custom marker
            $this->removeIterationPart($tree);

            $modifiedTree = $this->transformToTree($tree);

            if (!isset($tree['name'])) {
                throw new \RuntimeException('Root category in the tree does not exists');
            }

            $this->headerFooterData = [$tree['name'] => $modifiedTree];
        }

        return $this->headerFooterData;
    }

    /**
     * Returns import mapper
     */
    public function getImportMapper(): array
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
    public function saveMapper(string $key, string $value): void
    {
        $this->importMapper[$key] = $value;
    }

    public function saveBufferData(string $adapter, string $key, ?string $value): void
    {
        $this->bufferData[$adapter][$key] = $value;
    }

    /**
     * @return array<string, array<mixed>>
     */
    public function getRawData(): array
    {
        return $this->rawData;
    }

    public function setRawData(string $type, ?array $rawData): void
    {
        $this->rawData[$type][] = $rawData;
    }

    public function getBufferData(string $type): ?array
    {
        if (isset($this->bufferData[$type])) {
            return $this->bufferData[$type];
        }

        return null;
    }

    /**
     * @param array<int|string, string> $bufferData
     */
    public function setBufferData(array $bufferData): void
    {
        $this->bufferData = $bufferData;
    }

    public function unsetBufferData(string $type): void
    {
        unset($this->bufferData[$type]);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getData(): ?array
    {
        return $this->data;
    }

    /**
     * @param array<string, array<mixed>> $data
     */
    public function setData(array $data): void
    {
        $this->data = $data;
    }

    public function getMainType(): ?string
    {
        return $this->mainType;
    }

    public function setMainType(string $mainType): void
    {
        $this->mainType = $mainType;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getPreparedData(string $type, string $recordLink): ?array
    {
        if (\is_null($this->preparedData[$type])) {
            $data = $this->getData();

            foreach ($data[$type] as $record) {
                $this->preparedData[$type][$record[$recordLink]][] = $record;
            }
        }

        return $this->preparedData[$type];
    }

    /**
     * Create iteration node mapper for import
     *
     * @param array<string, mixed> $node
     */
    protected function createIterationNodeMapper(array $node, ?string $nodePath = null): void
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
    protected function generateMapper(array $node, ?string $nodePath = null): void
    {
        $separator = null;

        if ($nodePath) {
            $separator = '_';
        }

        if (isset($node['children'])) {
            if (isset($node['attributes'])) {
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
    protected function removeIterationPart(array &$node): void
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
     * and puts db data into this formated array
     *
     * @param array<string, string> $mapper
     * @param array<string, mixed>  $node
     */
    private function transformToTree(array $node, array $mapper = null, string $adapterType = null)
    {
        if (isset($node['adapter']) && \is_array($node) && $node['adapter'] != $adapterType) {
            return $this->buildIterationNode($node);
        }

        $currentNode = [];

        if (!isset($node['rawKey']) && isset($node['children']) && \is_array($node) && \count($node['children']) > 0) {
            if (isset($node['attributes'])) {
                foreach ($node['attributes'] as $attribute) {
                    $currentNode['_attributes'][$attribute['name']] = $mapper[$attribute['shopwareField']];
                }
            }

            foreach ($node['children'] as $child) {
                $currentNode[$child['name']] = $this->transformToTree($child, $mapper, $node['adapter']);
            }
        } else {
            if (isset($node['attributes']) && \is_array($node)) {
                foreach ($node['attributes'] as $attribute) {
                    $currentNode['_attributes'][$attribute['name']] = $mapper[$attribute['shopwareField']];
                }

                $currentNode['_value'] = $mapper[$node['shopwareField']];
            } else {
                $currentNode = $mapper[$node['shopwareField']];
                if ($node['type'] === 'raw') {
                    $currentNode = $mapper[$node['rawKey']];

                    if (isset($node['children']) && \is_array($node) && \count($node['children']) > 0) {
                        foreach ($node['children'] as $child) {
                            $currentNode[$child['name']] = $this->transformToTree($child, $currentNode, $node['adapter']);
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
}
