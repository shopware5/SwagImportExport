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
            ORDER BY c.parent, c.position
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
            ORDER BY c.parent, c.position
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

    public function import()
    {
        
    }

}
