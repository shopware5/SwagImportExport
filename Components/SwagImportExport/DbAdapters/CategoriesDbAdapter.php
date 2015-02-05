<?php

namespace Shopware\Components\SwagImportExport\DbAdapters;

use Doctrine\Common\Collections\ArrayCollection;
use Shopware\Models\Category\Category;
use Shopware\Components\SwagImportExport\Utils\DbAdapterHelper;
use \Shopware\Components\SwagImportExport\Utils\SnippetsHelper as SnippetsHelper;
use Shopware\Components\SwagImportExport\Exception\AdapterException;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\Validator\Constraints\DateTime;

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

        $builder = $this->getBuilder($columns, $ids);

        $categories = $builder->getQuery()->getArrayResult();
        $categories = $this->prepareCategoriesForMultipleCustomerGroups($categories);

        $result['default'] = DbAdapterHelper::decodeHtmlEntities($categories);

        return $result;
    }

    private function prepareCategoriesForMultipleCustomerGroups($categories)
    {
        $returnValue = array();
        $all = $categories;
        foreach($all as $categorie) {
            if(empty($categorie['customerGroups'])) {
                $returnValue[] = $categorie;
            } else {
                foreach($categorie['customerGroups'] as $customerGroup) {
                    $newCategorie = $categorie;
                    unset($newCategorie['customerGroups']);
                    $newCategorie['customerGroups'] = $customerGroup['id'];
                    $returnValue[] = $newCategorie;
                }
            }
        }
        return $returnValue;
    }

    /**
     * Returns default categories columns name 
     * and category attributes
     * 
     * @return array
     */
    public function getDefaultColumns()
    {
        $columns = array(
            'c.id',
            'c.parentId',
            'c.name',
            'c.position',
            'c.metaKeywords',
            'c.metaDescription',
            'c.cmsHeadline',
            'c.cmsText',
            'c.template',
            'c.active',
            'c.blog',
            'c.showFilterGroups',
            'c.external',
            'c.hideFilter',
            'c.customerGroups',
        );

        // Attributes
        $attributesSelect = $this->getAttributes();

        if ($attributesSelect && !empty($attributesSelect)) {
            $columns = array_merge($columns, $attributesSelect);
        }

        return $columns;
    }

    /**
     * @param $columns
     * @param $ids
     * @return \Doctrine\ORM\QueryBuilder|\Shopware\Components\Model\QueryBuilder
     */
    public function getBuilder($columns, $ids)
    {
        $builder = $this->getManager()->createQueryBuilder();
        $builder->select('c,attr, customerGroups')
            ->from('Shopware\Models\Category\Category', 'c')
            ->leftJoin('c.attribute', 'attr')
            ->leftJoin('c.customerGroups', 'customerGroups')
            ->Where('c.id IN (:ids)')
            ->setParameter('ids', $ids);

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
     */
    public function write($records)
    {
        $records = Shopware()->Events()->filter(
                'Shopware_Components_SwagImportExport_DbAdapters_CategoriesDbAdapter_Write',
                $records,
                array('subject' => $this)
        );

        $manager = $this->getManager();

        foreach ($records['default'] as $record) {
            try{
                if (!$record['name']) {
                    $message = SnippetsHelper::getNamespace()
                        ->get('adapters/categories/name_required', 'Category name is required');
                    throw new AdapterException($message);
                }

                if (!$record['parentId']) {
                    $message = SnippetsHelper::getNamespace()
                        ->get('adapters/categories/parent_id_required', 'Parent category id is required for category %s');
                    throw new AdapterException(sprintf($message, $record['name']));
                }

                $parentCategory = $this->getRepository()->findOneBy(array('id' => $record['parentId']));
                if (!$parentCategory) {
                    $message = SnippetsHelper::getNamespace()
                        ->get('adapters/categories/parent_not_exists', 'Parent category does not exists for category %s');
                    throw new AdapterException(sprintf($message, $record['name']));
                }

                $record = $this->prepareData($record);

                $category = $this->createCategory($record, $parentCategory);

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
     * Create the Category by hand. The method ->fromArray do not work
     *
     * @param array $record
     * @param Category $parent
     * @return Category
     */
    private function createCategory($record, Category $parent)
    {
        $recordAttribute = $record['attribute'];

        // create the Category
        /* @var $category Category */
        $category = $this->getManager()->getRepository('\Shopware\Models\Category\Category')->find($record['id']);

        if (!$category instanceof Category ) {
            $category = new Category();
            $category->fromArray($record);
        }

        $category->setParent($parent);

        // create the categoryAttributes
        $attributes = $this->getAttributeByCategoryId($record['id']);

        if(!$attributes instanceof \Shopware\Models\Attribute\Category) {
            $attributes = new \Shopware\Models\Attribute\Category();
            $attributes->fromArray($record);
            $attributes->setCategoryId($record['id']);
        }

        $attributes->setCategory($category);

        $category->setAttribute($attributes);

        // get avoided customerGroups
        $customerGroupId = (int)$record['customerGroups'];

        if ($customerGroupId) {
            $customerGroup = $this->getCustomerGroupById($customerGroupId);
            if ($customerGroup) {
                if (!$this->checkIfRelationExists($category->getId(), $customerGroup->getId())) {
                    $category->getCustomerGroups()->add($customerGroup);
                }
            }
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
     * @param int $categoryId
     * @return null|\Shopware\Models\Attribute\Category
     */
    private function getAttributeByCategoryId($categoryId)
    {
        $category = $this->getManager()->getRepository('\Shopware\Models\Attribute\Category')->findOneBy(array('categoryId' =>$categoryId));
        return $category;
    }

    /**
     * @param array $data
     * @return array
     */
    protected function prepareData(array $data)
    {
        //prepares the parent category
        $data['parent'] = $this->getRepository()->findOneBy(array('id' => $data['parentId']));

        //prepares the attributes
        foreach ($data as $key => $value) {
            if (preg_match('/^attribute/', $key)) {
                $newKey = lcfirst(preg_replace('/^attribute/', '', $key));
                $data['attribute'][$newKey] = $value;
                unset($data[$key]);
            }
        }

        return $data;
    }
    
    /**
     * @return array
     */
    public function getSections()
    {
        return array(
            array('id' => 'default', 'name' => 'default')
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

}
