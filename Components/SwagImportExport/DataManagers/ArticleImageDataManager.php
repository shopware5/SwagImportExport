<?php

namespace Shopware\Components\SwagImportExport\DataManagers;

class ArticleImageDataManager
{
    /** @var \Enlight_Components_Db_Adapter_Pdo_Mysql */
    private $db = null;

    /** Define which field should be set by default */
    private $defaultFields = array(
        'main',
        'position',
        'thumbnail',
        'description',
    );

    public function __construct()
    {
        $this->db = Shopware()->Db();
    }

    /**
     * Sets fields which are empty by default.
     *
     * @param array $record
     * @param int $articleId
     * @return mixed
     */
    public function setDefaultFields($record, $articleId)
    {
        foreach ($this->defaultFields as $key) {
            if (isset($record[$key])) {
                continue;
            }

            switch ($key) {
                case 'main':
                    $record[$key] = 1;
                    break;
                case 'position':
                    $record[$key] = $this->getPosition($articleId);
                    break;
                case 'thumbnail':
                    $record[$key] = true;
                    break;
                case 'description':
                    $record[$key] = '';
                    break;
            }
        }

        return $record;
    }

    /**
     * @param int $articleId
     * @return int
     */
    private function getPosition($articleId)
    {
        $sql = "SELECT MAX(position) FROM s_articles_img WHERE articleID = ?;";
        $result = $this->db->fetchOne($sql, $articleId);

        return isset($result) ? ((int) $result + 1) : 1;
    }
}