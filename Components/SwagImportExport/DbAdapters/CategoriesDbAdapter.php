<?php

namespace Shopware\Components\SwagImportExport\DbAdapters;

use Shopware\Models\Category\Category;
use Shopware\Components\SwagImportExport\Utils\DbAdapterHelper;
use \Shopware\Components\SwagImportExport\Utils\SnippetsHelper as SnippetsHelper;

class CategoriesDbAdapter implements DataDbAdapter
{

    /**
     * Shopware\Components\Model\ModelManager
     */
    protected $manager;

    /**
     * Shopware\Models\Category\Category
     */
    protected $repository;

    /**
     * @var array
     */
    protected $unprocessedData;

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
     * @param array $ids
     * @param array $columns
     * @return array
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

        $categories = $builder->getQuery()->getResult();

        $result['default'] = DbAdapterHelper::decodeHtmlEntities($categories);

        return $result;
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
            'c.hideFilter'
        );

        // Attributes
        $attributesSelect = $this->getAttributes();
        
        if ($attributesSelect && !empty($attributesSelect)) {
            $columns = array_merge($columns, $attributesSelect);
        }

        return $columns;
    }

    public function getUnprocessedData()
    {
        return $this->unprocessedData;
    }

    public function getAttributes()
    {
        $stmt = Shopware()->Db()->query("SHOW COLUMNS FROM s_categories_attributes");
        $columns = $stmt->fetchAll();

        $attributes = array();
        foreach ($columns as $column) {
            if ($column['Field'] !== 'id' && $column['Field'] !== 'categoryID') {
                $attributes[] = $column['Field'];
            }
        }

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

            if (!$record['parentId']) {
                $message = SnippetsHelper::getNamespace()
                    ->get('adapters/categories/parent_id_required', 'Parent category id is required');
                throw new \Exception($message);
            }

            if (!$record['name']) {
                $message = SnippetsHelper::getNamespace()
                    ->get('adapters/categories/name_required', 'Category name is required');
                throw new \Exception($message);
            }
            
            $parentCategory = $this->getRepository()->findOneBy(array('id' => $record['parentId']));
            if (!$parentCategory) {
                $message = SnippetsHelper::getNamespace()
                    ->get('adapters/categories/parent_not_exists', 'Parent category does not exists.');
                throw new \Exception($message);
            }
            
            $category = $this->getRepository()->findOneBy(array('id' => $record['id']));

            if (!$category) {
                $category = new Category();
            }

            $record = $this->prepareData($record);

            $category->fromArray($record);

            $violations = $manager->validate($category);

            if ($violations->count() > 0) {
                throw new \Exception($violations);
            }

            $manager->persist($category);
            $metadata = $manager->getClassMetaData(get_class($category));
            $metadata->setIdGeneratorType(\Doctrine\ORM\Mapping\ClassMetadata::GENERATOR_TYPE_NONE);
            $manager->flush();
            $manager->clear();
        }
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

    /**
     * Returns category repository
     * 
     * @return Shopware\Models\Category\Category
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
     * @return Shopware\Components\Model\ModelManager
     */
    public function getManager()
    {
        if ($this->manager === null) {
            $this->manager = Shopware()->Models();
        }

        return $this->manager;
    }

    public function getBuilder($columns, $ids)
    {
        $builder = $this->getManager()->createQueryBuilder();
        $builder->select($columns)
                ->from('Shopware\Models\Category\Category', 'c')
                ->leftJoin('c.attribute', 'attr')
                ->where('c.id IN (:ids)')
                ->setParameter('ids', $ids);

        return $builder;
    }

}
