<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Shopware\Components\SwagImportExport\DbAdapters;

use Shopware\Components\Model\ModelManager;
use Shopware\Components\Model\QueryBuilder;
use Shopware\Components\SwagImportExport\Utils\SnippetsHelper;
use Shopware\Components\SwagImportExport\Exception\AdapterException;
use Shopware\Components\SwagImportExport\Validators\ArticleTranslationValidator;
use Shopware\Models\Article\Detail;
use Shopware\Models\Article\Element;
use Shopware\Models\Shop\Shop;
use Shopware\Models\Translation\Translation;

class ArticlesTranslationsDbAdapter implements DataDbAdapter
{
    /** @var ModelManager */
    protected $manager;

    /** @var \Shopware_Components_Translation */
    protected $translationComponent;

    /** @var boolean */
    protected $importExportErrorMode;

    /** @var array */
    protected $unprocessedData;

    /** @var array */
    protected $logMessages;

    /** @var string */
    protected $logState;

    /** @var ArticleTranslationValidator */
    protected $validator;

    /** @var \Enlight_Components_Db_Adapter_Pdo_Mysql */
    protected $db;

    /** @var \Enlight_Event_EventManager */
    protected $eventManager;

    public function __construct()
    {
        $this->manager = Shopware()->Models();
        $this->validator = new ArticleTranslationValidator();
        $this->translationComponent = new \Shopware_Components_Translation();
        $this->importExportErrorMode = (boolean) Shopware()->Config()->get('SwagImportExportErrorMode');
        $this->db = Shopware()->Db();
        $this->eventManager = Shopware()->Events();
    }

    /**
     * @return array
     */
    public function getDefaultColumns()
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
            't.packUnit as packUnit'
        ];

        $elementBuilder = $this->getElementBuilder();

        $elements = $elementBuilder->getQuery()->getArrayResult();

        if ($elements) {
            foreach ($elements as $element) {
                $translation[] = 't.' . $element['name'];
            }
        }

        return $translation;
    }

    /**
     * @return array
     */
    public function getUnprocessedData()
    {
        return $this->unprocessedData;
    }

    /**
     * @param $start
     * @param $limit
     * @param $filter
     * @return array
     */
    public function readRecordIds($start, $limit, $filter)
    {
        $builder = $this->manager->createQueryBuilder();

        $builder->select('t.id')
            ->from(Translation::class, 't')
            ->leftJoin(Detail::class, 'ad', 'WITH', 'ad.id = t.key')
            ->where('t.type = :articleType')
            ->orWhere('t.type = :variantType AND ad.kind != :kind')
            ->setParameters(['articleType' => 'article', 'variantType' => 'variant', 'kind' => 1])
        ;

        if ($start) {
            $builder->setFirstResult($start);
        }

        if ($limit) {
            $builder->setMaxResults($limit);
        }

        $records = $builder->getQuery()->getArrayResult();
        $result = array_column($records, 'id');

        return $result;
    }

    /**
     * @param array $ids
     * @param array $columns
     * @return array
     * @throws \Exception
     */
    public function read($ids, $columns)
    {
        if (!$ids && empty($ids)) {
            $message = SnippetsHelper::getNamespace()
                ->get('adapters/translations/no_ids', 'Can not read translations without ids.');
            throw new \Exception($message);
        }

        if (!$columns && empty($columns)) {
            $message = SnippetsHelper::getNamespace()
                ->get('adapters/translations/no_column_names', 'Can not read translations without column names.');
            throw new \Exception($message);
        }

        $translations = $this->getTranslations($ids);

        $result['default'] = $this->prepareTranslations($translations);

        return $result;
    }

    /**
     * Processing serialized object data
     *
     * @param array $translations
     * @return array
     */
    protected function prepareTranslations($translations)
    {
        $translationAttr = $this->getElements();

        $articleMapper = [
            "txtArtikel" => "name",
            "txtshortdescription" => "description",
            "txtlangbeschreibung" => "descriptionLong",
            "txtkeywords" => "keywords",
            "metaTitle" => "metaTitle"
        ];

        $translationAttr['txtzusatztxt'] = 'additionalText';
        $translationAttr['txtpackunit'] = 'packUnit';

        if (!empty($translations)) {
            foreach ($translations as $index => $translation) {
                $variantData = unserialize($translation['variantData']);
                $articleData = unserialize($translation['articleData']);

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
     * @param $records
     * @throws AdapterException
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Doctrine\ORM\TransactionRequiredException
     * @throws \Enlight_Event_Exception
     * @throws \Exception
     */
    public function write($records)
    {
        if (empty($records['default'])) {
            $message = SnippetsHelper::getNamespace()
                ->get('adapters/articlesTranslations/no_records', 'No article translation records were found.');
            throw new \Exception($message);
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

        $whiteList = array_merge($whiteList, $variantWhiteList);

        $elementBuilder = $this->getElementBuilder();
        $attributes = $elementBuilder->getQuery()->getArrayResult();

        if ($attributes) {
            foreach ($attributes as $attr) {
                $whiteList[] = $attr['name'];
                $variantWhiteList[] = $attr['name'];
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
                    throw new AdapterException(sprintf($message, $record['languageId'], $record['articleNumber']));
                }

                $articleDetail = $articleDetailRepository->findOneBy(['number' => $record['articleNumber']]);

                if (!$articleDetail) {
                    $message = SnippetsHelper::getNamespace()
                        ->get('adapters/article_number_not_found', 'Article with order number %s doen not exists');
                    throw new AdapterException(sprintf($message, $record['articleNumber']));
                }

                $articleId = $articleDetail->getArticle()->getId();

                if ($articleDetail->getKind() === 1) {
                    $data = array_intersect_key($record, array_flip($whiteList));
                    $type = 'article';
                    $objectKey = $articleId;
                } else {
                    $data = array_intersect_key($record, array_flip($variantWhiteList));
                    $type = 'variant';
                    $objectKey = $articleDetail->getId();
                }

                if (!empty($data)) {
                    $this->translationComponent->write($shop->getId(), $type, $objectKey, $data);
                }
            } catch (AdapterException $e) {
                $message = $e->getMessage();
                $this->saveMessage($message);
            }
        }
    }

    /**
     * @param $message
     * @throws \Exception
     */
    public function saveMessage($message)
    {
        if ($this->importExportErrorMode === false) {
            throw new \Exception($message);
        }

        $this->setLogMessages($message);
        $this->setLogState('true');
    }

    /**
     * @return array
     */
    public function getLogMessages()
    {
        return $this->logMessages;
    }

    /**
     * @param $logMessages
     */
    public function setLogMessages($logMessages)
    {
        $this->logMessages[] = $logMessages;
    }

    /**
     * @return string
     */
    public function getLogState()
    {
        return $this->logState;
    }

    /**
     * @param $logState
     */
    public function setLogState($logState)
    {
        $this->logState = $logState;
    }

    /**
     * @return array
     */
    public function getSections()
    {
        return [
            ['id' => 'default', 'name' => 'default ']
        ];
    }

    /**
     * @param string $section
     * @return bool|mixed
     */
    public function getColumns($section)
    {
        $method = 'get' . ucfirst($section) . 'Columns';

        if (method_exists($this, $method)) {
            return $this->{$method}();
        }

        return false;
    }

    /**
     * @deprecated Element will be removed in shopware 5.3. Should be changed then.
     *
     * @return QueryBuilder
     */
    public function getElementBuilder()
    {
        $repository = $this->manager->getRepository(Element::class);

        $builder = $repository->createQueryBuilder('attribute');
        $builder->andWhere('attribute.translatable = 1');
        $builder->orderBy('attribute.position');

        return $builder;
    }

    /**
     * @return array
     */
    public function getElements()
    {
        $elementBuilder = $this->getElementBuilder();

        $elements = $elementBuilder->getQuery()->getArrayResult();

        $elementsCollection = [];

        foreach ($elements as $element) {
            $name = $element['name'];
            $elementsCollection[$name] = $name;
        }

        return $elementsCollection;
    }

    /**
     * @param $ids
     * @return array
     * @throws \Zend_Db_Statement_Exception
     */
    public function getTranslations($ids)
    {
        $translationIds = implode(',', $ids);

        $translationColumns = 'variant.id as id, variant.ordernumber as articleNumber, variant.kind as kind,
        ct.objectdata as variantData, ct2.objectdata as articleData, ct.objectlanguage as languageId';

        $sql = "(SELECT $translationColumns
                FROM `s_core_translations` AS ct

                LEFT JOIN s_articles AS article
                ON article.id = ct.objectkey

                LEFT JOIN s_articles_details AS variant
                ON variant.articleID = article.id

                LEFT JOIN `s_core_translations` AS ct2
                ON ct2.objectkey = variant.articleID AND ct2.objecttype = 'article' AND ct2.objectlanguage = ct.objectlanguage

                WHERE ct.id IN ($translationIds) AND ct.objecttype = 'article'
                GROUP BY article.id, languageId)

                UNION

                (SELECT $translationColumns
                FROM `s_core_translations` AS ct

                LEFT JOIN s_articles_details AS variant
                ON variant.id = ct.objectkey

                LEFT JOIN `s_core_translations` AS ct2
                ON ct2.objectkey = variant.articleID AND ct2.objecttype = 'article' AND ct2.objectlanguage = ct.objectlanguage

                WHERE ct.id IN ($translationIds) AND ct.objecttype = 'variant')

                ORDER BY languageId ASC
                ";

        return $this->db->query($sql)->fetchAll();
    }
}
