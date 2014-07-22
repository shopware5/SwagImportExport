<?php

namespace Shopware\Components\SwagImportExport\DbAdapters;

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
     * @return type
     */
    protected $db;

    public function getDefaultColumns()
    {
        return array(
            'd.ordernumber as articleNumber',
            't.languageID as languageId',
            't.name as title',
            't.keywords as keywords',
            't.description as description',
            't.description_long as descriptionLong',
            't.description_clear as descriptionClear',
            't.attr1 as attr1',
            't.attr2 as attr2',
            't.attr3 as attr3',
            't.attr4 as attr4',
            't.attr5 as attr5',
        );
    }

    public function readRecordIds($start, $limit, $filter)
    {
        $query = "SELECT id FROM s_articles_translations";

        $stmt = $this->getDb()->query($query);

        $records = $stmt->fetchAll();

        $result = array();
        if ($records) {
            foreach ($records as $value) {
                $result[] = $value['id'];
            }
        }

        return $result;
    }

    public function read($ids, $columns)
    {
        $columns = implode(',', $columns);
        $ids = implode(',', $ids);

        $query = "
                SELECT $columns
                FROM s_articles_translations as t
                LEFT JOIN (s_articles as a) ON a.id = t.articleID
                LEFT JOIN (s_articles_details as d) ON d.articleID = a.id
                WHERE d.kind = 1 AND t.id IN ($ids)";

        $stmt = $this->getDb()->query($query);

        $result['default'] = $stmt->fetchAll();

        return $result;
    }

    public function write($records)
    {
        $queryValues = array();
        
        foreach ($records['default'] as $index => $record) {

            if (!isset($record['articleNumber'])) {
                throw new \Exception('Article order number is required.');
            }
            
            if (isset($record['languageId'])) {
                $shop = $this->getManager()->find('Shopware\Models\Shop\Shop', $record['languageId']);
            }
            
            if (!$shop) {
                throw new \Exception('Language does not exists');
            }

            $articleDetail = $this->getRepository()->findOneBy(array('number' => $record['articleNumber']));

            if (!$articleDetail) {
                throw new \Exception('Article does not exists');
            }

            $articleId = (int) $articleDetail->getArticle()->getId();
            $languageID = (int) $record['languageId'];
            $name = $this->prepareValue($record['title']);
            $description = $this->prepareValue($record['description']);
            $descriptionLong = $this->prepareValue($record['descriptionLong']);
            $keywords = $this->prepareValue($record['keywords']);
            $descriptionClear = $this->prepareValue($record['descriptionClear']);
            $attr1 = $this->prepareValue($record['attr1']);
            $attr2 = $this->prepareValue($record['attr2']);
            $attr3 = $this->prepareValue($record['attr3']);
            $attr4 = $this->prepareValue($record['attr4']);
            $attr5 = $this->prepareValue($record['attr5']);
            
            $value = "($articleId, $languageID, '$name', '$description', '$descriptionLong', '$keywords',
                       '$descriptionClear', '$attr1', '$attr2', '$attr3', '$attr4', '$attr5' )";
            $queryValues[] = $value;

            unset($articleDetail);
        }
        
        $queryValues = implode(',', $queryValues);
        
        $query = "REPLACE INTO s_articles_translations (articleID, languageID, name, description, description_long,
                                                       keywords, description_clear, attr1, attr2, attr3, attr4, attr5) 
                 VALUES $queryValues";
        
        $this->getDb()->query($query);
    }
    
    protected function prepareValue($value)
    {
        $value = $value !== null ? ($value) : '';
        
        $value = mysql_escape_string($value);
        
        return $value;
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
    
    public function getDb()
    {
        if ($this->db === null) {
            $this->db = Shopware()->Db();
        }
        
        return $this->db;
    }

}
