<?php

namespace Shopware\Components\SwagImportExport\DbAdapters;

use \Shopware\Components\SwagImportExport\Utils\SnippetsHelper as SnippetsHelper;
use Shopware\Components\SwagImportExport\Exception\AdapterException;

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

    public function getDefaultColumns()
    {
        $translation = array(
            'd.ordernumber as articleNumber',
            't.languageID as languageId',
            't.name as name',
            't.keywords as keywords',
            't.description as description',
            't.description_long as descriptionLong',
            't.metaTitle as metaTitle',
        );

        $elementBuilder = $this->getElementBuilder();

        $elements = $elementBuilder->getQuery()->getArrayResult();

        if ($elements) {
            $elementsCollection = array();
            foreach ($elements as $element){
                $elementsCollection[] = 't.' . $element['name'];
            }

            $translation = array_merge($translation, $elementsCollection);
        }

        return $translation;
    }

    public function getUnprocessedData()
    {
        return $this->unprocessedData;
    }

    public function readRecordIds($start, $limit, $filter)
    {
        $manager = $this->getManager();

        $builder = $manager->createQueryBuilder();

        $builder->select('t.id');
        $builder->from('Shopware\Models\Translation\Translation', 't')
                ->leftJoin('Shopware\Models\Article\Article', 'article', \Doctrine\ORM\Query\Expr\Join::WITH, 'article.id=t.key')
                ->join('article.details', 'detail')
                ->where("t.type = 'article'")
                ->andWhere('detail.kind = 1');

        $builder->setFirstResult($start)
                ->setMaxResults($limit);

        $records = $builder->getQuery()->getResult();

        $result = array_map(
            function($item) {
                return $item['id'];
            }, $records
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

        $builder = $this->getBuilder($ids);

        $translations = $builder->getQuery()->getResult();

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
        $elements = $this->getElements();

        $translationFields = array(
            "txtArtikel" => "name",
            "txtzusatztxt" => "additionaltext",
            "txtshortdescription" => "description",
            "txtlangbeschreibung" => "descriptionLong",
            "txtkeywords" => "keywords",
            "metaTitle" => "metaTitle"
        );

        if (!empty($elements)) {
            $translationFields = array_merge($translationFields, $elements);
        }

        if (!empty($translations)) {
            foreach ($translations as $index => $translation) {
                $objectdata = unserialize($translation['data']);

                if (!empty($objectdata)) {
                    foreach ($objectdata as $key => $value) {
                        if (isset($translationFields[$key])) {
                            $translations[$index][$translationFields[$key]] = $value;
                        }
                    }
                    unset($translations[$index]['data']);
                }
            }
        }

        return $translations;
    }

    public function write($records)
    {
        if ($records['default'] == null) {
            return;
        }

        $records = Shopware()->Events()->filter(
                'Shopware_Components_SwagImportExport_DbAdapters_ArticlesTranslationsDbAdapter_Write',
                $records,
                array('subject' => $this)
        );

        $whitelist = array(
            'name',
            'description',
            'descriptionLong',
            'keywords',
            'metaTitle',
            'packUnit'
        );

        $elementBuilder = $this->getElementBuilder();

        $elements = array_map(function($item) {
            return $item['name'];
        }, $elementBuilder->getQuery()->getArrayResult());

        if ($elements) {
            $whitelist = array_merge($whitelist, $elements);
        }

        $translationWriter = new \Shopware_Components_Translation();

        foreach ($records['default'] as $index => $record) {
            try {
                if (!isset($record['articleNumber'])) {
                    $message = SnippetsHelper::getNamespace()
                            ->get('adapters/ordernumber_required', 'Order number is required.');
                    throw new AdapterException($message);
                }

                if (isset($record['languageId'])) {
                    $shop = $this->getManager()->find('Shopware\Models\Shop\Shop', $record['languageId']);
                }

                if (!$shop) {
                    $message = SnippetsHelper::getNamespace()
                            ->get('adapters/articlesTranslations/lang_id_not_found', 'Language with id %s does not exists for article %s');
                    throw new \AdapterException(sprintf($message, $record['languageId'], $record['articleNumber']));
                }

                $articleDetail = $this->getRepository()->findOneBy(array('number' => $record['articleNumber']));

                if (!$articleDetail) {
                    $message = SnippetsHelper::getNamespace()
                            ->get('adapters/article_number_not_found', 'Article with order number %s doen not exists');
                    throw new AdapterException(sprintf($message, $record['articleNumber']));
                }

                $data = array_intersect_key($record, array_flip($whitelist));

                $articleId = (int) $articleDetail->getArticle()->getId();

                $translationWriter->write($shop->getId(), 'article', $articleId, $data);
            } catch (AdapterException $e) {
                $message = $e->getMessage();
                $this->saveMessage($message);
            }
        }
    }

    public function saveMessage($message)
    {
        $errorMode = Shopware()->Config()->get('SwagImportExportErrorMode');

        if ($errorMode === false) {
            throw new \Exception($message);
        }

        $this->setLogMessages($message);
    }

    public function getLogMessages()
    {
        return $this->logMessages;
    }

    public function setLogMessages($logMessages)
    {
        $this->logMessages[] = $logMessages;
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
     * @return mix
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
     * @return Shopware\Models\Article\Detail
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
     * @return Shopware\Components\Model\ModelManager
     */
    public function getManager()
    {
        if ($this->manager === null) {
            $this->manager = Shopware()->Models();
        }

        return $this->manager;
    }

    public function getBuilder($ids)
    {
        $builder = $this->getManager()->createQueryBuilder();
        $builder->select(array('detail.number as articleNumber', 't.data', 't.key as articleId ', 't.localeId as languageId'))
                ->from('Shopware\Models\Translation\Translation', 't')
                ->leftJoin('Shopware\Models\Article\Article', 'article', \Doctrine\ORM\Query\Expr\Join::WITH, 'article.id=t.key')
                ->join('article.details', 'detail')
                ->where('t.id IN (:ids)')
                ->andWhere('detail.kind = 1')
                ->setParameter('ids', $ids);

        return $builder;
    }

    public function getElementBuilder()
    {
        $repository = $this->getManager()->getRepository('Shopware\Models\Article\Element');

        $builder = $repository->createQueryBuilder('attribute');
        $builder->andWhere('attribute.translatable = 1');
        $builder->orderBy('attribute.position');

        return $builder;
    }

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

}
