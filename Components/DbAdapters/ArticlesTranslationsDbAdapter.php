<?php
declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport\Components\DbAdapters;

use Shopware\Components\Model\ModelManager;
use Shopware\Models\Article\Detail;
use Shopware\Models\Attribute\Configuration;
use Shopware\Models\Shop\Shop;
use Shopware\Models\Translation\Translation;
use SwagImportExport\Components\Exception\AdapterException;
use SwagImportExport\Components\Utils\SnippetsHelper;
use SwagImportExport\Components\Validators\ArticleTranslationValidator;

class ArticlesTranslationsDbAdapter implements DataDbAdapter, \Enlight_Hook
{
    protected ModelManager $manager;

    protected \Shopware_Components_Translation $translationComponent;

    protected bool $importExportErrorMode;

    /**
     * @var array<string>
     */
    protected array $logMessages = [];

    protected ?string $logState = null;

    protected ArticleTranslationValidator $validator;

    protected \Enlight_Components_Db_Adapter_Pdo_Mysql $db;

    protected \Enlight_Event_EventManager $eventManager;

    public function __construct(
        ModelManager $manager,
        \Shopware_Components_Translation $translationComponent,
        \Enlight_Components_Db_Adapter_Pdo_Mysql $db,
        \Enlight_Event_EventManager $eventManager,
        \Shopware_Components_Config $config
    ) {
        $this->validator = new ArticleTranslationValidator();
        $this->manager = $manager;
        $this->translationComponent = $translationComponent;
        $this->importExportErrorMode = (bool) $config->get('SwagImportExportErrorMode');
        $this->db = $db;
        $this->eventManager = $eventManager;
    }

    public function supports(string $adapter): bool
    {
        return $adapter === DataDbAdapter::ARTICLE_TRANSLATION_ADAPTER;
    }

    /**
     * {@inheritDoc}
     */
    public function getDefaultColumns(): array
    {
        $translation = [
            'd.ordernumber as articleNumber',
            't.languageID as languageId',
            't.name as name',
            't.keywords as keywords',
            't.description as description',
            't.description_long as descriptionLong',
            't.additional_text as additionalText',
            't.metaTitle as metaTitle',
            't.packUnit as packUnit',
        ];

        $attributes = $this->getAttributes();

        if ($attributes) {
            foreach ($attributes as $attribute) {
                $translation[] = 't.' . $attribute['columnName'];
            }
        }

        return $translation;
    }

    /**
     * {@inheritDoc}
     */
    public function getUnprocessedData(): array
    {
        return [];
    }

    /**
     * {@inheritDoc}
     */
    public function readRecordIds(?int $start, ?int $limit, array $filter = []): array
    {
        $builder = $this->manager->createQueryBuilder();

        $builder->select('t.id')
            ->from(Translation::class, 't')
            ->where('t.type IN (:types)')
            ->setParameter('types', ['article', 'variant']);

        if ($start) {
            $builder->setFirstResult($start);
        }

        if ($limit) {
            $builder->setMaxResults($limit);
        }

        $records = $builder->getQuery()->getArrayResult();

        return \array_column($records, 'id');
    }

    public function read(array $ids, array $columns): array
    {
        if (empty($ids)) {
            $message = SnippetsHelper::getNamespace()
                ->get('adapters/translations/no_ids', 'Can not read translations without ids.');
            throw new \Exception($message);
        }

        $translations = $this->getTranslations($ids);

        $result['default'] = $this->prepareTranslations($translations);

        return $result;
    }

    /**
     * @throws \Enlight_Event_Exception
     * @throws \RuntimeException
     */
    public function write(array $records): void
    {
        if (empty($records['default'])) {
            $message = SnippetsHelper::getNamespace()
                ->get('adapters/articlesTranslations/no_records', 'No article translation records were found.');
            throw new \RuntimeException($message);
        }

        $records = $this->eventManager->filter(
            'Shopware_Components_SwagImportExport_DbAdapters_ArticlesTranslationsDbAdapter_Write',
            $records,
            ['subject' => $this]
        );

        $whiteList = [
            'name',
            'description',
            'descriptionLong',
            'metaTitle',
            'keywords',
        ];

        $variantWhiteList = [
            'additionalText',
            'packUnit',
        ];

        $whiteList = \array_merge($whiteList, $variantWhiteList);

        $attributes = $this->getAttributes();

        if ($attributes) {
            foreach ($attributes as $attribute) {
                $whiteList[] = $attribute['columnName'];
                $variantWhiteList[] = $attribute['columnName'];
            }
        }

        $articleDetailRepository = $this->manager->getRepository(Detail::class);
        $shopRepository = $this->manager->getRepository(Shop::class);

        foreach ($records['default'] as $index => $record) {
            try {
                $record = $this->validator->filterEmptyString($record);
                $this->validator->checkRequiredFields($record);
                $this->validator->validate($record, ArticleTranslationValidator::$mapper);

                $shop = false;
                if (isset($record['languageId'])) {
                    $shop = $shopRepository->find($record['languageId']);
                }

                if (!$shop) {
                    $message = SnippetsHelper::getNamespace()
                        ->get('adapters/articlesTranslations/lang_id_not_found', 'Language with id %s does not exists for article %s');
                    throw new AdapterException(\sprintf($message, $record['languageId'], $record['articleNumber']));
                }

                $articleDetail = $articleDetailRepository->findOneBy(['number' => $record['articleNumber']]);

                if (!$articleDetail instanceof Detail) {
                    $message = SnippetsHelper::getNamespace()
                        ->get('adapters/article_number_not_found', 'Article with order number %s doen not exists');
                    throw new AdapterException(\sprintf($message, $record['articleNumber']));
                }

                $articleId = $articleDetail->getArticle()->getId();

                if ($articleDetail->getKind() === 1) {
                    $data = \array_intersect_key($record, \array_flip($whiteList));
                    $type = 'article';
                    $objectKey = $articleId;
                } else {
                    $data = \array_intersect_key($record, \array_flip($variantWhiteList));
                    $type = 'variant';
                    $objectKey = $articleDetail->getId();
                }
                if (!empty($data)) {
                    $data = $this->prepareAttributePrefix($data, $attributes);

                    $this->translationComponent->write($shop->getId(), $type, $objectKey, $data);
                }
            } catch (AdapterException $e) {
                $message = $e->getMessage();
                $this->saveMessage($message);
            }
        }
    }

    /**
     * @throws \Exception
     */
    public function saveMessage(string $message): void
    {
        if ($this->importExportErrorMode === false) {
            throw new \Exception($message);
        }

        $this->setLogMessages($message);
        $this->setLogState('true');
    }

    /**
     * @return array<string>
     */
    public function getLogMessages(): array
    {
        return $this->logMessages;
    }

    public function setLogMessages(string $logMessages): void
    {
        $this->logMessages[] = $logMessages;
    }

    public function getLogState(): ?string
    {
        return $this->logState;
    }

    public function setLogState(string $logState): void
    {
        $this->logState = $logState;
    }

    public function getSections(): array
    {
        return [
            ['id' => 'default', 'name' => 'default '],
        ];
    }

    public function getColumns(string $section): array
    {
        $method = 'get' . \ucfirst($section) . 'Columns';

        if (\method_exists($this, $method)) {
            return $this->{$method}();
        }

        return [];
    }

    /**
     * @param array<int> $ids
     *
     * @return array<string, string>
     */
    public function getTranslations(array $ids): array
    {
        $translationIds = \implode(',', $ids);

        $sql = "
            (SELECT ad.id as id, ad.ordernumber as articleNumber, ad.kind as kind,
                    t.objectdata as articleData, t.objectdata as variantData, t.objectlanguage as languageId
            FROM s_core_translations t
            LEFT JOIN s_articles a ON (t.objectkey = a.id)
            LEFT JOIN s_articles_details ad ON (ad.articleID = a.id AND ad.kind = 1)
            WHERE t.id IN ($translationIds) AND t.objecttype = 'article')

            UNION

            (SELECT  ad.id as id, ad.ordernumber as articleNumber, ad.kind as kind,
                    t.objectdata as articleData, t.objectdata as variantData, t.objectlanguage as languageId
            FROM s_core_translations t
            LEFT JOIN s_articles_details ad ON (t.objectkey = ad.id)
            WHERE t.id IN ($translationIds) AND t.objecttype = 'variant')

            ORDER BY languageId ASC
        ";

        return $this->db->query($sql)->fetchAll();
    }

    /**
     * Processing serialized object data
     *
     * @param array<string, mixed> $translations
     *
     * @return array<array<string, string>>
     */
    protected function prepareTranslations(array $translations): array
    {
        $translationAttr = [];

        $attributes = [];
        foreach ($this->getAttributes() as $attribute) {
            $attributes['__attribute_' . $attribute['columnName']] = $attribute['columnName'];
        }

        $articleMapper = [
            'txtArtikel' => 'name',
            'txtshortdescription' => 'description',
            'txtlangbeschreibung' => 'descriptionLong',
            'txtkeywords' => 'keywords',
            'metaTitle' => 'metaTitle',
        ];

        $translationAttr['txtzusatztxt'] = 'additionalText';
        $translationAttr['txtpackunit'] = 'packUnit';
        $translationAttr = \array_merge($translationAttr, $attributes);

        if (!empty($translations)) {
            foreach ($translations as $index => $translation) {
                $variantData = \unserialize($translation['variantData']);
                $articleData = \unserialize($translation['articleData']);

                if (!empty($articleData)) {
                    foreach ($articleData as $articleKey => $value) {
                        if (isset($articleMapper[$articleKey])) {
                            $translations[$index][$articleMapper[$articleKey]] = $value;
                        }
                    }
                }

                if (!empty($variantData)) {
                    foreach ($variantData as $variantKey => $value) {
                        if (isset($translationAttr[$variantKey])) {
                            $translations[$index][$translationAttr[$variantKey]] = $value;
                        }
                    }
                }

                unset($translations[$index]['articleData']);
                unset($translations[$index]['variantData']);
            }
        }

        return $translations;
    }

    /**
     * @return array<array<string>>
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
            ->getArrayResult();
    }

    /**
     * Prefix attributes before writing to database
     *
     * @param array<string, mixed> $data
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
