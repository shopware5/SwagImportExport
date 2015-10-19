<?php

namespace Shopware\Components\SwagImportExport\DbAdapters;

use Shopware\Components\SwagImportExport\DataManagers\CategoriesDataManager;
use Shopware\Components\SwagImportExport\DataType\CategoryDataType;
use Shopware\Models\Category\Category;
use Shopware\Components\SwagImportExport\Utils\DbAdapterHelper;
use Shopware\Components\SwagImportExport\Utils\SnippetsHelper;
use Shopware\Components\SwagImportExport\Exception\AdapterException;
use Shopware\Components\SwagImportExport\Validators\CategoryValidator;

class CategoriesDbAdapter implements DataDbAdapter
{

    /**
     * Shopware\Components\Model\ModelManager
     */
    protected $manager;

    /** @var $db  */
    protected $db;

    /**
     * Shopware\Models\Category\Category
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

    /** @var CategoryValidator */
    protected $validator;

    /** @var CategoriesDataManager */
    protected $dataManager;

    /**
     * @var array
     */
    protected $defaultValues;

    /**
     * Returns record ids
     * 
     * @param int $start
     * @param int $limit
     * @param type $filter
     * @return array
     */
    public function readRecordIds($start = null, $limit = null, $filter = null)
    {
        $manager = $this->getManager();

        $builder = $manager->createQueryBuilder();

        $builder->select('c.id');

        $builder->from('Shopware\Models\Category\Category', 'c')
                ->where('c.id != 1')
                ->orderBy('c.parentId', 'ASC');

        $builder->setFirstResult($start)
                ->setMaxResults($limit);

        $records = $builder->getQuery()->getResult();

        $result = array();
        if ($records) {
            foreach ($records as $value) {
                $result[] = $value['id'];
            }
        }

        return $result;
    }

    /**
     * Returns categories
     *
     * @param $ids
     * @param $columns
     * @return mixed
     *
     * @throws \Exception
     */
    public function read($ids, $columns)
    {
        if (!$ids && empty($ids)) {
            $message = SnippetsHelper::getNamespace()
                    ->get('adapters/categories/no_ids', 'Can not read categories without ids.');
            throw new \Exception($message);
        }

        if (!$columns && empty($columns)) {
            $message = SnippetsHelper::getNamespace()
                    ->get('adapters/categories/no_column_names', 'Can not read categories without column names.');
            throw new \Exception($message);
        }

        $builder = $this->getBuilder($columns['default'], $ids);

        $categories = $builder->getQuery()->getArrayResult();

        $result['default'] = DbAdapterHelper::decodeHtmlEntities($categories);
        $result['customerGroups'] = $this->getBuilder($this->getCustomerGroupsColumns(), $ids)->getQuery()->getResult();

        return $result;
    }

    /**
     * @param $columns
     * @param $ids
     * @return \Doctrine\ORM\QueryBuilder|\Shopware\Components\Model\QueryBuilder
     */
    public function getBuilder($columns, $ids)
    {
        $builder = $this->getManager()->createQueryBuilder();
        $builder->select($columns)
            ->from('Shopware\Models\Category\Category', 'c')
            ->leftJoin('c.attribute', 'attr')
            ->leftJoin('c.customerGroups', 'customerGroups')
            ->Where('c.id IN (:ids)')
            ->setParameter('ids', $ids)
            ->distinct();

        return $builder;
    }

    public function getUnprocessedData()
    {
        return $this->unprocessedData;
    }

    public function getAttributes()
    {
        $db = $this->getDb();
        $stmt = $db->query("SHOW COLUMNS FROM s_categories_attributes");
        $columns = $stmt->fetchAll();
        $attributes = $this->getFieldNames($columns);

        $attributesSelect = '';
        if ($attributes) {
            $prefix = 'attr';
            $attributesSelect = array();
            foreach ($attributes as $attribute) {
                //underscore to camel case
                //exmaple: underscore_to_camel_case -> underscoreToCamelCase
                $catAttr = preg_replace("/\_(.)/e", "strtoupper('\\1')", $attribute);

                $attributesSelect[] = sprintf('%s.%s as attribute%s', $prefix, $catAttr, ucwords($catAttr));
            }
        }

        return $attributesSelect;
    }

    /**
     * Helper method: Filtered the field names and return them
     *
     * @param $columns
     * @return array
     */
    private function getFieldNames($columns)
    {
        $attributes = array();
        foreach ($columns as $column) {
            if ($column['Field'] !== 'id' && $column['Field'] !== 'categoryID') {
                $attributes[] = $column['Field'];
            }
        }
        return $attributes;
    }

    /**
     * Insert/Update data into db
     * 
     * @param array $records
     * @throws \Enlight_Event_Exception
     * @throws \Exception
     */
    public function write($records)
    {
        if (empty($records['default'])) {
            $message = SnippetsHelper::getNamespace()->get(
                'adapters/categories/no_records',
                'No category records were found.'
            );
            throw new \Exception($message);
        }

        $records = Shopware()->Events()->filter(
            'Shopware_Components_SwagImportExport_DbAdapters_CategoriesDbAdapter_Write',
            $records,
            array('subject' => $this)
        );

        $manager = $this->getManager();
        $validator = $this->getValidator();
        $dataManager = $this->getDataManager();

        $defaultValues = $this->getDefaultValues();

        foreach ($records['default'] as $index => $record) {
            try {
                $record = $validator->prepareInitialData($record);

                $category = $this->findExistingEntries($record);
                if (!$category instanceof Category) {
                    $record = $dataManager->setDefaultFieldsForCreate($record, $defaultValues);
                    $category = new Category();
                    if (isset($record['categoryId'])) {
                        $category->setId($record['categoryId']);
                    }
                }

                $validator->checkRequiredFields($record);
                $validator->validate($record, CategoryDataType::$mapper);

                $record['parent'] = $this->getRepository()->findOneBy(array('id' => $record['parentId']));
                if (!$record['parent'] instanceof Category) {
                    $message = SnippetsHelper::getNamespace()
                        ->get('adapters/categories/parent_not_exists', 'Parent category does not exists for category %s');
                    throw new AdapterException(sprintf($message, $record['name']));
                }

               $record = $this->prepareData($record, $index, $category->getId(), $records['customerGroups']);

                $category->fromArray($record);

                $violations = $manager->validate($category);
                if ($violations->count() > 0) {
                    $message = SnippetsHelper::getNamespace()
                                    ->get('adapters/category/no_valid_category_entity', 'No valid category entity for category %s');
                    throw new AdapterException(sprintf($message, $category->getName()));
                }

                $manager->persist($category);

                $metadata = $manager->getClassMetaData(get_class($category));
                $metadata->setIdGeneratorType(\Doctrine\ORM\Mapping\ClassMetadata::GENERATOR_TYPE_NONE);

                $manager->flush();
                $manager->clear();
            } catch (AdapterException $e) {
                $message = $e->getMessage();
                $this->saveMessage($message);
            }
        }
    }

    /**
     * @param array $array
     * @param int $currentIndex
     * @return array
     */
    private function getCustomerGroupIdsFromIndex(array $array, $currentIndex)
    {
        $returnArray = array();
        foreach ($array as $customerGroupEntry) {
            if ($customerGroupEntry['parentIndexElement'] == $currentIndex) {
                $returnArray[] = $customerGroupEntry['customerGroupId'];
            }
        }

        return $returnArray;
    }

    /**
     * Create the Category by hand. The method ->fromArray do not work
     *
     * @param $record
     * @return Category
     */
    private function findExistingEntries($record)
    {
        /* @var $category Category */
        if (isset($record['categoryId'])) {
            $category = $this->getRepository()->find($record['categoryId']);
        }

        return $category;
    }

    private $categoryAvoidCustomerGroups = null;

    private function checkIfRelationExists($categoryId, $customerGroupId)
    {
        if ($this->categoryAvoidCustomerGroups === null) {
            $this->setCategoryAvoidCustomerGroups();
        }

        foreach ($this->categoryAvoidCustomerGroups as $relation) {
            if ($relation['categoryID'] == $categoryId && $relation['customergroupID'] == $customerGroupId) {
                return true;
            }
        }

        return false;
    }

    private function setCategoryAvoidCustomerGroups()
    {
        $sql = "SELECT categoryID, customergroupID FROM s_categories_avoid_customergroups";
        $this->categoryAvoidCustomerGroups = $this->getDb()->fetchAll($sql);
    }


    /**
     * @param int $id
     * @return null|\Shopware\Models\Customer\Group
     */
    private function getCustomerGroupById($id)
    {
        /** @var \Shopware\Models\Customer\Group $group */
        $group = $this->getManager()->getRepository('Shopware\Models\Customer\Group')->find($id);
        return $group;
    }

    /**
     * @param array $data
     * @param $index
     * @param $categoryId
     * @param $groups
     * @return array
     */
    protected function prepareData(array $data, $index, $categoryId, $groups)
    {
        //prepares attribute associated data
        foreach ($data as $key => $value) {
            if (preg_match('/^attribute/', $key)) {
                $newKey = lcfirst(preg_replace('/^attribute/', '', $key));
                $data['attribute'][$newKey] = $value;
                unset($data[$key]);
            }
        }

        //prepares customer groups associated data
        $customerGroups = array();
        $customerGroupIds = $this->getCustomerGroupIdsFromIndex($groups, $index);
        foreach($customerGroupIds as $customerGroupID) {
            $customerGroup = $this->getCustomerGroupById($customerGroupID);
            if ($customerGroup && !$this->checkIfRelationExists($categoryId, $customerGroup->getId())) {
                $customerGroups[] = $customerGroup;
            }
        }
        $data['customerGroups'] = $customerGroups;

        unset($data['parentId']);

        return $data;
    }
    
    /**
     * @return array
     */
    public function getSections()
    {
        return array(
            array('id' => 'default', 'name' => 'default'),
            array('id' => 'customerGroups', 'name' => 'CustomerGroups'),
        );
    }

    public function getParentKeys($section)
    {
        switch ($section) {
            case 'customerGroups':
                return array(
                    'c.id as categoryId',
                );
        }
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

    public function getCustomerGroupsColumns()
    {
        return array(
            'c.id as categoryId',
            'customerGroups.id as customerGroupId',
        );
    }

    /**
     * Returns default categories columns name
     * and category attributes
     *
     * @return array
     */
    public function getDefaultColumns()
    {
        $columns['default'] = array(
            'c.id as categoryId',
            'c.parentId as parentId',
            'c.name as name',
            'c.position as position',
            'c.metaTitle as metaTitle',
            'c.metaKeywords as metaKeywords',
            'c.metaDescription as metaDescription',
            'c.cmsHeadline as cmsHeadline',
            'c.cmsText as cmsText',
            'c.template as template',
            'c.active as active',
            'c.blog as blog',
            'c.showFilterGroups as showFilterGroups',
            'c.external as external',
            'c.hideFilter as hideFilter',
        );

        // Attributes
        $attributesSelect = $this->getAttributes();

        if ($attributesSelect && !empty($attributesSelect)) {
            $columns['default'] = array_merge($columns['default'], $attributesSelect);
        }

        return $columns;
    }

    /**
     * Return list with default values for fields which are empty or don't exists
     *
     * @return array
     */
    private function getDefaultValues()
    {
        return $this->defaultValues;
    }

    /**
     * Set default values for fields which are empty or don't exists
     *
     * @param array $values default values for nodes
     */
    public function setDefaultValues($values)
    {
        $this->defaultValues = $values;
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
     * Returns category repository
     * 
     * @return \Shopware\Models\Category\Category
     */
    public function getRepository()
    {
        if ($this->repository === null) {
            $this->repository = $this->getManager()->getRepository('Shopware\Models\Category\Category');
        }
        return $this->repository;
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

    public function getDb()
    {
        if($this->db === null) {
            $this->db = Shopware()->Db();
        }
        return $this->db;
    }

    public function getValidator()
    {
        if ($this->validator === null) {
            $this->validator = new CategoryValidator();
        }

        return $this->validator;
    }

    public function getDataManager()
    {
        if ($this->dataManager === null) {
            $this->dataManager = new CategoriesDataManager();
        }

        return $this->dataManager;
    }
}
