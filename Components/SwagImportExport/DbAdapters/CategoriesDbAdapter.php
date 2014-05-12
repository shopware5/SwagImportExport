<?php

namespace Shopware\Components\SwagImportExport\DbAdapters;

class CategoriesDbAdapter implements DataDbAdapter
{

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
        $sqlLimit = '';
        if ($start !== null && $limit !== null) {
            $sqlLimit = "LIMIT {$start},{$limit}";
        }

        $sql = "
            SELECT
                c.id
            FROM s_categories c
            WHERE c.id != 1
            ORDER BY c.id ASC
            $sqlLimit 
        ";
        $stmt = Shopware()->Db()->query($sql);
        $records = $stmt->fetchAll();

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
     * @return array
     */
    public function read($ids, $columns)
    {
        $sql = "
            SELECT
                $columns
            FROM s_categories c
            LEFT JOIN s_categories_attributes attr
                ON attr.categoryID = c.id
            WHERE c.id IN ($ids)
        ";

        $stmt = Shopware()->Db()->query($sql);
        $result = $stmt->fetchAll();

        return $result;
    }

    /**
     * Returns default categories columns name
     * 
     * @return string
     */
    public function getDefaultColumns()
    {
        $columns = 'c.id,
                    c.parent,
                    c.description,
                    c.position,
                    c.metakeywords,
                    c.metadescription,
                    c.cmsheadline,
                    c.cmstext,
                    c.template,
                    c.active,
                    c.blog,
                    c.showfiltergroups,
                    c.external,
                    c.hidefilter';

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
                $attributesSelect[] = sprintf('%s.%s as attribute_%s', $prefix, $attribute, $attribute);
            }

            $attributesSelect = ",\n" . implode(",\n", $attributesSelect);
        }

        return $columns . $attributesSelect;
    }

    /**
     * Insert/Update data into db
     * 
     * @param array $records
     */
    public function write($records)
    {
        $columnNames = $this->getColumnNames(current($records));
        
        $queryValues = $this->getQueryValues($records);
        
        $query = "REPLACE INTO `s_categories` ($columnNames) VALUES $queryValues ;";
        
        Shopware()->Db()->query($query);
    }

    /**
     * Returns column names
     * 
     * @param array $data
     * @return string
     */
    public function getColumnNames($data)
    {
        foreach ($data as $columnName => $value) {
            $columnNames[] = $columnName;
        }
        
        $columnNames = "`" . implode("`,`", $columnNames) . "`";
        
        return $columnNames;
    }
    
    /**
     * Returns query values i.e. (3,1,'Deutsch','0'), (39,1,'English','0')
     * 
     * @param array $data
     * @return string
     * @throws Exception
     */
    public function getQueryValues($data)
    {
        $lastKey = end(array_keys(current($data)));
        
        $queryValues = '';
        
        foreach ($data as $category) {
            $tempData = null;
            
            //todo: make better check for the categories !
            if (empty($category['id']) || empty($category['parent']) || empty($category['description'])) {
                throw new Exception('Categories requires id, parent and description');
            }
            
            foreach ($category as $key => $value) {
                
                $comma = $key == $lastKey ? '' : ',';
                
                $tempData .= !(int)($value) ? "'" . $value . "'" : $value;
                $tempData .= $comma;
            }
            
            $queryValues .= ', (' . $tempData . ')';
        }
        
        //removes the first comma
        $queryValues[0] = ' ';
        
        return $queryValues;
    }
}
