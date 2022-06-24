<?php
declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport\Components\DbAdapters\Products;

use Doctrine\DBAL\Connection;
use Shopware\Components\Model\ModelManager;
use Shopware\Models\Attribute\Configuration;
use Shopware_Components_Translation as TranslationComponent;
use SwagImportExport\Components\Exception\AdapterException;
use SwagImportExport\Components\Utils\SnippetsHelper;

class TranslationWriter
{
    private ModelManager $manager;

    private Connection $connection;

    private TranslationComponent $writer;

    private array $shops;

    /**
     * initialises the class properties
     */
    public function __construct(
        ModelManager $manager,
        Connection $connection,
        TranslationComponent $writer
    ) {
        $this->manager = $manager;
        $this->connection = $connection;
        $this->writer = $writer;
        $this->shops = $this->getShops();
    }

    /**
     * @param array<int, array<string, string|int>> $translations
     *
     * @throws AdapterException
     */
    public function write(int $productId, int $productDetailId, int $mainDetailId, array $translations): void
    {
        $whiteList = [
            'name',
            'description',
            'descriptionLong',
            'metaTitle',
            'keywords',
            'shippingTime',
        ];

        $variantWhiteList = [
            'additionalText',
            'packUnit',
            'shippingTime',
        ];

        $whiteList = \array_merge($whiteList, $variantWhiteList);

        // covers 5.2 attribute system
        $attributes = $this->getAttributes();

        if ($attributes) {
            foreach ($attributes as $attribute) {
                $whiteList[] = $attribute['columnName'];
                $variantWhiteList[] = $attribute['columnName'];
            }
        }

        foreach ($translations as $translation) {
            if (!$this->isValid($translation)) {
                continue;
            }

            $languageId = $translation['languageId'];

            if (!$this->getShop((int) $languageId)) {
                $message = SnippetsHelper::getNamespace()
                    ->get('adapters/articles/no_shop_id', 'Shop by id %s not found');
                throw new AdapterException(\sprintf($message, $languageId));
            }

            if ($productDetailId === $mainDetailId) {
                $data = $this->filterWhitelistedFields($translation, $whiteList);
                $data = $this->prepareAttributePrefix($data, $attributes);

                $this->writer->write($languageId, 'article', $productId, $data);
            } else {
                $data = $this->filterWhitelistedFields($translation, $variantWhiteList);

                // checks for empty translations
                if (!empty($data)) {
                    foreach ($data as $index => $rows) {
                        // removes empty rows
                        if (empty($rows)) {
                            unset($data[$index]);
                        }
                    }
                }

                // saves if there is available data
                if (!empty($data)) {
                    $data = $this->prepareAttributePrefix($data, $attributes);

                    $this->writer->write($languageId, 'variant', $productDetailId, $data);
                }
            }
        }
    }

    /**
     * Returns all shops
     *
     * @return array<int|string, mixed>
     */
    public function getShops(): array
    {
        return $this->connection->fetchAllKeyValue('SELECT `id`, `name` FROM s_core_shops');
    }

    public function getShop(int $shopId): ?string
    {
        return $this->shops[$shopId];
    }

    /**
     * @param array<string, mixed> $translation
     */
    private function isValid(array $translation): bool
    {
        if (!isset($translation['languageId']) || empty($translation['languageId'])) {
            return false;
        }

        return true;
    }

    /**
     * @return array<array<string,string>>
     */
    private function getAttributes(): array
    {
        $repository = $this->manager->getRepository(Configuration::class);

        return $repository->createQueryBuilder('configuration')
            ->select('configuration.columnName')
            ->where('configuration.tableName = :tablename')
            ->andWhere('configuration.translatable = 1')
            ->setParameter('tablename', 's_articles_attributes')
            ->getQuery()
            ->getArrayResult()
        ;
    }

    /**
     * @param array<string, mixed> $translation
     * @param array<int, mixed>    $whiteList
     */
    private function filterWhitelistedFields(array $translation, array $whiteList): array
    {
        return \array_intersect_key($translation, \array_flip($whiteList));
    }

    /**
     * @param array<string, mixed> $attributes
     *
     * @return array<string, string>
     */
    private function prepareAttributePrefix(array $data, array $attributes): array
    {
        $result = [];
        $attributes = \array_column($attributes, 'columnName');

        foreach ($data as $field => $translation) {
            if (\in_array($field, $attributes)) {
                $result['__attribute_' . $field] = $translation;
                continue;
            }
            $result[$field] = $translation;
        }

        return $result;
    }
}
