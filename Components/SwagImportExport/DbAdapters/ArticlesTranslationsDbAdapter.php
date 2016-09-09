<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Shopware\Components\SwagImportExport\DbAdapters;

use Doctrine\ORM\Query\Expr\Join;
use Shopware\Components\Model\ModelManager;
use Shopware\Components\Model\QueryBuilder;
use Shopware\Components\SwagImportExport\Utils\SnippetsHelper;
use Shopware\Components\SwagImportExport\Exception\AdapterException;
use Shopware\Components\SwagImportExport\Validators\ArticleTranslationValidator;

class ArticlesTranslationsDbAdapter implements DataDbAdapter
{
    /**
     * Shopware\Components\Model\ModelManager
     */
    protected $manager;

    /**
     * Shopware\Models\Article\Article
     */
    protected $repository;

    /**
     * @var array
     */
    protected $unprocessedData;

    /**
     * @var array
     */
    protected $logMessages;

    /**
     * @var string
     */
    protected $logState;

    /**
     * @var ArticleTranslationValidator
     */
    protected $validator;

    /**
     * @return array
     */
    public function getDefaultColumns()
    {
        $translation = array(
            'd.ordernumber as articleNumber',
            't.languageID as languageId',
            't.name as name',
            't.keywords as keywords',
            't.description as description',
            't.description_long as descriptionLong',
            't.additional_text as additionalText',
            't.metaTitle as metaTitle',
            't.packUnit as packUnit',
        );

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
        $manager = $this->getManager();

        $builder = $manager->createQueryBuilder();

        $builder->select('t.id');
        $builder->from('Shopware\Models\Translation\Translation', 't')
            ->where("t.type = 'variant'")
            ->orWhere("t.type = 'article'");

        if ($start) {
            $builder->setFirstResult($start);
        }

        if ($limit) {
            $builder->setMaxResults($limit);
        }

        $records = $builder->getQuery()->getResult();

        $result = array_map(
            function ($item) {
                return $item['id'];
            },
            $records
        );

        return $result;
    }

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

        $articleMapper = array(
            "txtArtikel" => "name",
            "txtshortdescription" => "description",
            "txtlangbeschreibung" => "descriptionLong",
            "txtkeywords" => "keywords",
            "metaTitle" => "metaTitle"
        );

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

        $records = Shopware()->Events()->filter(
            'Shopware_Components_SwagImportExport_DbAdapters_ArticlesTranslationsDbAdapter_Write',
            $records,
            array('subject' => $this)
        );

        $whiteList = array(
            'name',
            'description',
            'descriptionLong',
            'metaTitle',
            'keywords',
        );

        $variantWhiteList = array(
            'additionalText',
            'packUnit',
        );

        $whiteList = array_merge($whiteList, $variantWhiteList);

        $elementBuilder = $this->getElementBuilder();
        $attributes = $elementBuilder->getQuery()->getArrayResult();

        if ($attributes) {
            foreach ($attributes as $attr) {
                $whiteList[] = $attr['name'];
                $variantWhiteList[] = $attr['name'];
            }
        }

        $validator = $this->getValidator();
        $translationWriter = new \Shopware_Components_Translation();

        foreach ($records['default'] as $index => $record) {
            try {
                $record = $validator->prepareInitialData($record);
                $validator->checkRequiredFields($record);
                $validator->validate($record, ArticleTranslationValidator::$mapper);

                if (isset($record['languageId'])) {
                    $shop = $this->getManager()->find('Shopware\Models\Shop\Shop', $record['languageId']);
                }

                if (!$shop) {
                    $message = SnippetsHelper::getNamespace()
                        ->get('adapters/articlesTranslations/lang_id_not_found', 'Language with id %s does not exists for article %s');
                    throw new AdapterException(sprintf($message, $record['languageId'], $record['articleNumber']));
                }

                $articleDetail = $this->getRepository()->findOneBy(array('number' => $record['articleNumber']));

                if (!$articleDetail) {
                    $message = SnippetsHelper::getNamespace()
                        ->get('adapters/article_number_not_found', 'Article with order number %s doen not exists');
                    throw new AdapterException(sprintf($message, $record['articleNumber']));
                }

                $articleId = (int) $articleDetail->getArticle()->getId();

                if ($articleDetail->getKind() === 1) {
                    $data = array_intersect_key($record, array_flip($whiteList));
                    $translationWriter->write($shop->getId(), 'article', $articleId, $data);
                } else {
                    $data = array_intersect_key($record, array_flip($variantWhiteList));

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
                        $translationWriter->write($shop->getId(), 'variant', $articleDetail->getId(), $data);
                    }
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
        $errorMode = Shopware()->Config()->get('SwagImportExportErrorMode');

        if ($errorMode === false) {
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
        return array(
            array('id' => 'default', 'name' => 'default ')
        );
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
     * Returns article detail repository
     *
     * @return \Shopware\Models\Article\Repository
     */
    public function getRepository()
    {
        if ($this->repository === null) {
            $this->repository = $this->getManager()->getRepository('Shopware\Models\Article\Detail');
        }

        return $this->repository;
    }

    /**
     * Returns entity manager
     *
     * @return ModelManager
     */
    public function getManager()
    {
        if ($this->manager === null) {
            $this->manager = Shopware()->Models();
        }

        return $this->manager;
    }

    /**
     * @param $ids
     * @return QueryBuilder
     */
    public function getBuilder($ids)
    {
        $builder = $this->getManager()->createQueryBuilder();
        $builder->select(array('detail.number as articleNumber', 't.data', 't.key as articleId ', 't.localeId as languageId'))
            ->from('Shopware\Models\Translation\Translation', 't')
            ->leftJoin('Shopware\Models\Article\Article', 'article', Join::WITH, 'article.id=t.key')
            ->join('article.details', 'detail')
            ->where('t.id IN (:ids)')
            ->setParameter('ids', $ids);

        return $builder;
    }

    /**
     * @return QueryBuilder
     */
    public function getElementBuilder()
    {
        $repository = $this->getManager()->getRepository('Shopware\Models\Article\Element');

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

        $elementsCollection = array();

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
        $articleDetailIds = implode(',', $ids);

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

                WHERE ct.id IN ($articleDetailIds) AND ct.objecttype = 'article'
                GROUP BY article.id, languageId)

                UNION

                (SELECT $translationColumns
                FROM `s_core_translations` AS ct

                LEFT JOIN s_articles_details AS variant
                ON variant.id = ct.objectkey

                LEFT JOIN `s_core_translations` AS ct2
                ON ct2.objectkey = variant.articleID AND ct2.objecttype = 'article' AND ct2.objectlanguage = ct.objectlanguage

                WHERE ct.id IN ($articleDetailIds) AND ct.objecttype = 'variant')

                ORDER BY languageId ASC
                ";

        return Shopware()->Db()->query($sql)->fetchAll();
    }

    /**
     * @return ArticleTranslationValidator
     */
    public function getValidator()
    {
        if ($this->validator === null) {
            $this->validator = new ArticleTranslationValidator();
        }

        return $this->validator;
    }
}
