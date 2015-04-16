<?php

namespace Shopware\Components\SwagImportExport\DbAdapters;

use \Shopware\Components\SwagImportExport\Utils\SnippetsHelper as SnippetsHelper;
use Shopware\Components\SwagImportExport\Exception\AdapterException;

class TranslationsDbAdapter implements DataDbAdapter
{

    protected $manager;

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
            ->where("t.type = 'propertyvalue'")
            ->orWhere("t.type = 'propertyvalue'")
            ->orWhere("t.type = 'propertyoption'")
            ->orWhere("t.type = 'propertygroup'")
            ->orWhere("t.type = 'configuratoroption'")
            ->orWhere("t.type = 'configuratorgroup'");

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

        $translations = $this->getTranslations($ids);

        $result['default'] = $this->prepareTranslations($translations);

        return $result;
    }

    public function prepareTranslations($translations)
    {
        $mapper = array(
            'configuratorgroup' => 'name',
            'configuratoroption' => 'name',
            'propertygroup' => 'groupName',
            'propertyoption' => 'optionName',
            'propertyvalue' => 'optionValue',
        );

        $result = array();
        foreach ($translations as $index => $translation){

            $data = unserialize($translation['objectdata']);

            //key for different translation types
            $key = $mapper[$translation['objecttype']];

            $result[] = array(
                'objectKey' => $translation['objectkey'],
                'objectType' => $translation['objecttype'],
                'baseName' => $translation['baseName'],
                'name' => $data[$key],
                'description' => $data['description'],
                'languageId' => $translation['languageId'],
            );
        }


        return $result;
    }

    public function getDefaultColumns()
    {
        $translation = array(
            'objectKey',
            'objectType',
            'baseName',
            'name',
            'description',
            'languageId',
        );

        return $translation;
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

    public function write($records)
    {
        // TODO: Implement write() method.
    }

    public function getUnprocessedData()
    {
        // TODO: Implement getUnprocessedData() method.
    }

    public function getLogMessages()
    {
        // TODO: Implement getLogMessages() method.
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
        $builder->select('translation')
            ->from('Shopware\Models\Translation\Translation', 'translation')
            ->where('translation.id IN (:ids)')
            ->setParameter('ids', $ids);

        return $builder;
    }

    /**
     * @param $ids
     * @return array
     * @throws \Zend_Db_Statement_Exception
     */
    public function getTranslations($ids)
    {
        $articleDetailIds = implode(',', $ids);

        $translationColumns = 'ct.objecttype, ct.objectkey, ct.objectdata as objectdata, ct.objectlanguage as languageId';

        $sql = "(SELECT cgroup.name as baseName, $translationColumns
                FROM `s_core_translations` AS ct

                LEFT JOIN s_article_configurator_groups AS cgroup
                ON cgroup.id = ct.objectkey

                WHERE ct.id IN ($articleDetailIds) AND ct.objecttype = 'configuratorgroup')

                UNION

                (SELECT coptions.name as baseName, $translationColumns
                FROM `s_core_translations` AS ct

                LEFT JOIN s_article_configurator_options AS coptions
                ON coptions.id = ct.objectkey

                WHERE ct.id IN ($articleDetailIds) AND ct.objecttype = 'configuratoroption')

                UNION

                (SELECT pgroup.name as baseName, $translationColumns
                FROM `s_core_translations` AS ct

                LEFT JOIN s_filter AS pgroup
                ON pgroup.id = ct.objectkey

                WHERE ct.id IN ($articleDetailIds) AND ct.objecttype = 'propertygroup')

                UNION

                (SELECT poptions.name as baseName, $translationColumns
                FROM `s_core_translations` AS ct

                LEFT JOIN s_filter_options AS poptions
                ON poptions.id = ct.objectkey

                WHERE ct.id IN ($articleDetailIds) AND ct.objecttype = 'propertyoption')

                UNION

                (SELECT pvalues.value as baseName, $translationColumns
                FROM `s_core_translations` AS ct

                LEFT JOIN s_filter_values AS pvalues
                ON pvalues.id = ct.objectkey

                WHERE ct.id IN ($articleDetailIds) AND ct.objecttype = 'propertyvalue')
                ";

        return Shopware()->Db()->query($sql)->fetchAll();
    }
}