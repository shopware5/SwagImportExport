<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport\Components\DbAdapters\Articles;

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
     * @param int   $articleId
     * @param int   $articleDetailId
     * @param int   $mainDetailId
     * @param array $translations
     *
     * @throws AdapterException
     */
    public function write($articleId, $articleDetailId, $mainDetailId, $translations)
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

            if (!$this->getShop($languageId)) {
                $message = SnippetsHelper::getNamespace()
                    ->get('adapters/articles/no_shop_id', 'Shop by id %s not found');
                throw new AdapterException(\sprintf($message, $languageId));
            }

            if ($articleDetailId === $mainDetailId) {
                $data = $this->filterWhitelistedFields($translation, $whiteList);
                $data = $this->prepareAttributePrefix($data, $attributes);

                $this->writer->write($languageId, 'article', $articleId, $data);
            } else {
                $data = $this->filterWhitelistedFields($translation, $variantWhiteList);

                //checks for empty translations
                if (!empty($data)) {
                    foreach ($data as $index => $rows) {
                        //removes empty rows
                        if (empty($rows)) {
                            unset($data[$index]);
                        }
                    }
                }

                //saves if there is available data
                if (!empty($data)) {
                    $data = $this->prepareAttributePrefix($data, $attributes);

                    $this->writer->write($languageId, 'variant', $articleDetailId, $data);
                }
            }
        }
    }

    /**
     * Returns all shops
     *
     * @return array
     */
    public function getShops()
    {
        $shops = [];
        $result = $this->connection->fetchAll('SELECT `id`, `name` FROM s_core_shops');

        foreach ($result as $row) {
            $shops[$row['id']] = $row['name'];
        }

        return $shops;
    }

    /**
     * @param int $shopId
     *
     * @return string
     */
    public function getShop($shopId)
    {
        return $this->shops[$shopId];
    }

    /**
     * @return bool
     */
    private function isValid($translation)
    {
        if (!isset($translation['languageId']) || empty($translation['languageId'])) {
            return false;
        }

        return true;
    }

    /**
     * @return array
     */
    private function getAttributes()
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
     * @param array $translation
     * @param array $whiteList
     *
     * @return array
     */
    private function filterWhitelistedFields($translation, $whiteList)
    {
        return \array_intersect_key($translation, \array_flip($whiteList));
    }

    /**
     * @return array
     */
    private function prepareAttributePrefix(array $data, array $attributes)
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
