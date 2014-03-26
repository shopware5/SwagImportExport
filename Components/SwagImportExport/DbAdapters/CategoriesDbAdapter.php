<?php

namespace Shopware\Components\SwagImportExport\DbAdapters;

class CategoriesDbAdapter extends DataDbAdapter
{

    private $repository;

    /**
     * Returns categories 
     * 
     * @return array
     */
    public function getRawData()
    {
        //get columns from attribute tables
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

        //gets limit adapter
        $dapterLimit = $this->getDataAdapterLimit();
        $limit = $dapterLimit->getLimit();
        $offset = $dapterLimit->getOffset();

        $sql = "
            SELECT
                c.id as categoryID,
                c.parent as parentID,
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
                c.hidefilter
                $attributesSelect
            FROM s_categories c
            LEFT JOIN s_categories_attributes attr
                ON attr.categoryID = c.id
            WHERE c.id != 1
            ORDER BY c.parent, c.position
            LIMIT {$offset},{$limit}
        ";

        $stmt = Shopware()->Db()->query($sql);
        $result = $stmt->fetchAll();

        return $result;
    }

    public function import()
    {
        
    }

    public function getRepository()
    {
        if ($this->repository === null) {
            $this->repository = $this->getManager()->Category();
        }

        return $this->repository;
    }

}
