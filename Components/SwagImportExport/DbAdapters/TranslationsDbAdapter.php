<?php

namespace Shopware\Components\SwagImportExport\DbAdapters;

use Shopware\Components\SwagImportExport\Utils\SnippetsHelper as SnippetsHelper;
use Shopware\Components\SwagImportExport\Exception\AdapterException;

class TranslationsDbAdapter implements DataDbAdapter
{

    protected $manager;

    protected $configuratorGroupRepo;
    protected $configuratorOptionRepo;

    protected $propertyGroupRepo;
    protected $propertyOptionRepo;
    protected $propertyValueRepo;

    protected $logMessages;

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
        $mapper = $this->getElemenetMapper();

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
        if ($records['default'] == null) {
            return;
        }

        $records = Shopware()->Events()->filter(
            'Shopware_Components_SwagImportExport_DbAdapters_TranslationsDbAdapter_Write',
            $records,
            array('subject' => $this)
        );

        $importMapper = $this->getElemenetMapper();

        $translationWriter = new \Shopware_Components_Translation();

        foreach ($records['default'] as $index => $record) {
            try {

                if (!isset($record['objectType']) || empty($record['objectType'])) {
                    $message = SnippetsHelper::getNamespace()
                        ->get('adapters/translations/object_type_not_found', 'Object type is required.');
                    throw new AdapterException($message);
                }

                if (isset($record['languageId'])) {
                    $shop = $this->getManager()->find('Shopware\Models\Shop\Shop', $record['languageId']);
                }

                if (!$shop) {
                    $message = SnippetsHelper::getNamespace()
                        ->get('adapters/translations/lang_id_not_found', 'Language with id %s does not exists');
                    throw new AdapterException(sprintf($message, $record['languageId']));
                }

                $repository = $this->getRepository($record['objectType']);

                if (!isset($record['objectKey']) && !empty($record['objectKey'])){
                    $element = $repository->findOneBy(array('id' => (int) $record['objectKey']));

                    if(!$element){
                        $message = SnippetsHelper::getNamespace()
                            ->get('adapters/translations/element_id_not_found', '%s element not found with ID %s');
                        throw new AdapterException(sprintf($message, $record['objectType'], $record['objectKey']));
                    }
                } else if (isset($record['baseName']) && !empty($record['baseName'])){
                    $findKey = $record['objectType'] === 'propertyvalue' ? 'value': 'name';
                    $element = $repository->findOneBy(array($findKey => $record['baseName']));

                    if(!$element){
                        $message = SnippetsHelper::getNamespace()
                            ->get('adapters/translations/element_baseName_not_found', '%s element not found with name %s');
                        throw new AdapterException(sprintf($message, $record['objectType'], $record['baseName']));
                    }
                }

                if (!$element){
                    $message = SnippetsHelper::getNamespace()
                        ->get('adapters/translations/element_objectKey_baseName_not_found', 'Please provide objectKey or baseName');
                    throw new AdapterException(sprintf($message));
                }

                if (!isset($record['name'])){
                    $message = SnippetsHelper::getNamespace()
                        ->get('adapters/translations/element_name_not_found', 'Please provide name');
                    throw new AdapterException(sprintf($message));
                }

                $key = $importMapper[$record['objectType']];
                $data[$key] = $record['name'];

                if($record['objectType'] == 'configuratorgroup'){
                    $data['description'] = $record['description'];
                }

                $translationWriter->write($shop->getId(), $record['objectType'], $element->getId(), $data);

                unset($shop);
                unset($element);
                unset($data);

            } catch (AdapterException $e) {
                $message = $e->getMessage();
                $this->saveMessage($message);
            }
        }
    }

    public function getUnprocessedData()
    {

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
     * Returns entity manager
     *
     * @return \Shopware\Components\Model\ModelManager
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

    protected function getElemenetMapper()
    {
        return array(
            'configuratorgroup' => 'name',
            'configuratoroption' => 'name',
            'propertygroup' => 'groupName',
            'propertyoption' => 'optionName',
            'propertyvalue' => 'optionValue',
        );
    }

    /**
     * @param $ids
     * @return array
     * @throws \Zend_Db_Statement_Exception
     */
    protected function getTranslations($ids)
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

    protected function getRepository($type)
    {
        switch ($type) {
            case 'configuratorgroup':
                return $this->getConfiguratorGroupRepository();
            case 'configuratoroption':
                return $this->getConfiguratorOptionRepository();
            case 'propertygroup':
                return $this->getPropertyGroupRepository();
            case 'propertyoption':
                return $this->getPropertyOptionRepository();
            case 'propertyvalue':
                return $this->getPropertyValueRepository();
            default:
                $message = SnippetsHelper::getNamespace()
                    ->get('adapters/translations/object_type_not_existing', 'Object type %s not existing.');
                throw new AdapterException(sprintf($message, $type));

        }
    }

    /**
     * Returns configurator group repository
     *
     * @return \Shopware\Models\Article\Configurator\Group
     */
    public function getConfiguratorGroupRepository()
    {
        if ($this->configuratorGroupRepo === null) {
            $this->configuratorGroupRepo = $this->getManager()->getRepository('Shopware\Models\Article\Configurator\Group');
        }

        return $this->configuratorGroupRepo;
    }

    /**
     * Returns configurator option repository
     *
     * @return \Shopware\Models\Article\Configurator\Option
     */
    public function getConfiguratorOptionRepository()
    {
        if ($this->configuratorOptionRepo === null) {
            $this->configuratorOptionRepo = $this->getManager()->getRepository('Shopware\Models\Article\Configurator\Option');
        }

        return $this->configuratorOptionRepo;
    }

    /**
     * Returns property group repository
     *
     * @return \Shopware\Models\Property\Group
     */
    public function getPropertyGroupRepository()
    {
        if ($this->propertyGroupRepo === null) {
            $this->propertyGroupRepo = $this->getManager()->getRepository('Shopware\Models\Property\Group');
        }

        return $this->propertyGroupRepo;
    }

    /**
     * Returns property option repository
     *
     * @return \Shopware\Models\Property\Option
     */
    public function getPropertyOptionRepository()
    {
        if ($this->propertyOptionRepo === null) {
            $this->propertyOptionRepo = $this->getManager()->getRepository('Shopware\Models\Property\Option');
        }

        return $this->propertyOptionRepo;
    }

    /**
     * Returns property value repository
     *
     * @return \Shopware\Models\Property\Value
     */
    public function getPropertyValueRepository()
    {
        if ($this->propertyValueRepo === null) {
            $this->propertyValueRepo = $this->getManager()->getRepository('Shopware\Models\Property\Value');
        }

        return $this->propertyValueRepo;
    }
}