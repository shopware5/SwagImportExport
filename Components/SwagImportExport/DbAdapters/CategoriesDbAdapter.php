<?php

namespace Shopware\Components\SwagImportExport\DbAdapters;

use Shopware\Models\Category\Category;

class CategoriesDbAdapter implements DataDbAdapter
{
    /*
     * Shopware\Components\Model\ModelManager
     */

    private $manager;

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
     * @param type $ids
     * @param type $columns
     * @return array
     */
    public function read($ids, $columns)
    {
        if (!$ids && empty($ids)) {
            throw new \Exception('Can not read categories without ids.');
        }
        
        if (!$columns && empty($columns)) {
            throw new \Exception('Can not read categories without column names.');
        }
        
        $manager = $this->getManager();
        
        $builder = $manager->createQueryBuilder();
        $builder->select($columns)
                ->from('Shopware\Models\Category\Category', 'c')
                ->leftJoin('c.attribute', 'attr')
                ->where('c.id IN (:ids)')
                ->setParameter('ids', $ids);

        $result = $builder->getQuery()->getResult();
        

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
        $stmt = Shopware()->Db()->query('SELECT * FROM s_categories_attributes LIMIT 1');
        $attributes = $stmt->fetch();

        $attributesSelect = '';
        if ($attributes) {
            unset($attributes['id']);
            unset($attributes['categoryID']);
            $attributes = array_keys($attributes);

            $prefix = 'attr';
            $attributesSelect = array();
            foreach ($attributes as $attribute) {
                //underscore to camel case
                //exmaple: underscore_to_camel_case -> underscoreToCamelCase
                $catAttr = preg_replace("/\_(.)/e", "strtoupper('\\1')", $attribute);

                $attributesSelect[] = sprintf('%s.%s as attribute%s', $prefix, $catAttr, ucwords($catAttr));
            }
            //$attributesSelect = ",\n" . implode(",\n", $attributesSelect);
        }

        if ($attributesSelect && !empty($attributesSelect)) {
            $columns = array_merge($columns, $attributesSelect);            
        }
        
        return $columns;
    }

    /**
     * Insert/Update data into db
     * 
     * @param array $records
     */
    public function write($records)
    {
        $manager = $this->getManager();

        $catRepo = $manager->getRepository('Shopware\Models\Category\Category');

        foreach ($records as $record) {
            
            //todo: maybe create option to force the id ?
            if (!$record['id']){
                //todo: log this result
                continue;
            }
            
            $category = $catRepo->findOneBy(array('id' => $record['id']));
            
            if (!$category) {
                $category = new Category();
                $category->setId($record['id']);                
            }

            $parentCat = $catRepo->findOneBy(array('id' => $record['parentId']));

            if (!$parentCat) {
                //todo: log this result
                continue;
            }

            $category->setParent($parentCat);
            
            if (!$record['name']){
                //todo: log this result
                continue;
            }
            $category->setName($record['name']);
            
            if ($record['active']) {
                $category->setActive($record['active']);
            }

            $manager->persist($category);
            $metadata = $manager->getClassMetaData(get_class($category));
            $metadata->setIdGeneratorType(\Doctrine\ORM\Mapping\ClassMetadata::GENERATOR_TYPE_NONE);
            $manager->flush();
        }
        
        
//        $columnNames = $this->getColumnNames(current($records));
//
//        $queryValues = $this->getQueryValues($records);
//
//        $query = "REPLACE INTO `s_categories` ($columnNames) VALUES $queryValues ;";
//
//        Shopware()->Db()->query($query);
    }

//    /**
//     * Returns column names
//     * 
//     * @param array $data
//     * @return string
//     */
//    public function getColumnNames($data)
//    {
//        foreach ($data as $columnName => $value) {
//            $columnNames[] = $columnName;
//        }
//
//        $columnNames = "`" . implode("`,`", $columnNames) . "`";
//
//        return $columnNames;
//    }
//
//    /**
//     * Returns query values i.e. (3,1,'Deutsch','0'), (39,1,'English','0')
//     * 
//     * @param array $data
//     * @return string
//     * @throws Exception
//     */
//    public function getQueryValues($data)
//    {
//        $lastKey = end(array_keys(current($data)));
//
//        $queryValues = '';
//
//        foreach ($data as $category) {
//            $tempData = null;
//
//            //todo: make better check for the categories !
//            if (empty($category['id']) || empty($category['parent']) || empty($category['description'])) {
//                throw new Exception('Categories requires id, parent and description');
//            }
//
//            foreach ($category as $key => $value) {
//
//                $comma = $key == $lastKey ? '' : ',';
//
//                $tempData .=!(int) ($value) ? "'" . $value . "'" : $value;
//                $tempData .= $comma;
//            }
//
//            $queryValues .= ', (' . $tempData . ')';
//        }
//
//        //removes the first comma
//        $queryValues[0] = ' ';
//
//        return $queryValues;
//    }

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

}
