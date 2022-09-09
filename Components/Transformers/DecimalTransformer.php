<?php
declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport\Components\Transformers;

use Shopware\Components\Plugin\Configuration\ReaderInterface;
use SwagImportExport\Components\Profile\Profile;

class DecimalTransformer implements DataTransformerAdapter
{
    public const TYPE = 'decimals';

    private const DECIMAL_VALUES = [
        'weight',
        'width',
        'height',
        'length',
        'tax',
        'purchaseUnit',
        'referenceUnit',
        'price',
        'pseudoPrice',
        'purchasePrice',
        'regulationPrice',
        'invoiceAmount',
        'invoiceAmountNet',
        'invoiceShipping',
        'invoiceShippingNet',
    ];

    private bool $useCommaDecimal = false;

    private Profile $profile;

    private array $treeData;

    private ReaderInterface $reader;

    public function __construct(ReaderInterface $reader)
    {
        $this->reader = $reader;
    }

    public function supports(string $type): bool
    {
        return $type === self::TYPE;
    }

    /**
     * Sets the main config which defines the data restructuring
     */
    public function initialize(Profile $profile): void
    {
        $this->profile = $profile;
        $this->useCommaDecimal = $this->reader->getByPluginName('SwagImportExport')['useCommaDecimal'] ?? false;
        $this->treeData = $this->getTreeData();
    }

    /**
     * Transforms the data in direction to formatted output file and returns the transformed data.
     */
    public function transformForward(array $data): array
    {
        if (!$this->useCommaDecimal) {
            return $data;
        }

        return $this->transform($data);
    }

    /**
     * Transforms the data in direction from formatted output file and returns the transformed data.
     */
    public function transformBackward(array $data): array
    {
        if (!$this->useCommaDecimal) {
            return $data;
        }

        $data = $this->transform([$data], false);

        return $data[0];
    }

    /**
     * Transforms the data.
     * Direction (forward / backward) is given by the parameter $direction.
     */
    private function transform(array $data, bool $isForward = true): array
    {
        foreach ($data as &$records) {
            $records = $this->transformRecord($records, $isForward);
        }

        return $data;
    }

    /**
     * Transforms a record both forward and backwards.
     * It replaces the decimal-delimiter.
     */
    private function transformRecord(array $records, bool $isForward = true): array
    {
        foreach ($records as &$record) {
            foreach ($record as $key => &$value) {
                if (\is_array($value)) {
                    if (!$isForward) {
                        continue;
                    }

                    if ($this->isNode($key)) {
                        $value = $this->transformRecord([$value], $isForward);
                        $value = $value[0];
                    }

                    if ($this->isAdapter($key)) {
                        $value = $this->transformRecord($value, $isForward);
                    }

                    continue;
                }

                $realKey = $this->treeData['fields'][$key];

                if (!$realKey || !\in_array($realKey, self::DECIMAL_VALUES, true)) {
                    continue;
                }

                if ($isForward) {
                    $value = \str_replace('.', ',', (string) $value);
                    continue;
                }

                $value = \str_replace(',', '.', (string) $value);
            }
        }

        return $records;
    }

    /**
     * Returns all information known from the profile-tree.
     */
    private function getTreeData(): array
    {
        $profileTree = $this->getProfileTree();

        return $this->iterateTree($profileTree);
    }

    /**
     * Returns if the given key is an adapter.
     */
    private function isAdapter(string $key): bool
    {
        return isset($this->treeData['adapters'][$key]);
    }

    /**
     * Returns if the given key is a node.
     */
    private function isNode(string $key): bool
    {
        return isset($this->treeData['nodes'][$key]);
    }

    /**
     * Iterates recursively through the profile-tree and returns a flat-array of all the profile-fields.
     */
    private function iterateTree(array $currentNode, array $result = []): array
    {
        $currentEl = $currentNode;
        unset($currentEl['children']);

        switch ($currentNode['type']) {
            case 'leaf':
                $result['fields'][$currentNode['name']] = $currentNode['shopwareField'];

                return $result;
            case 'iteration':
                $result['adapters'][$currentNode['name']] = $currentEl;
                break;
            case 'node':
                $result['nodes'][$currentNode['name']] = $currentEl;
                break;
            default:
                $result['others'][$currentNode['name']] = $currentEl;
                break;
        }

        foreach ($currentNode['children'] ?? [] as $child) {
            $result = $this->iterateTree($child, $result);
        }

        return $result;
    }

    /**
     * Returns the profile tree.
     */
    private function getProfileTree(): array
    {
        return \json_decode($this->profile->getEntity()->getTree(), true);
    }
}
