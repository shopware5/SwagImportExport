<?php

namespace Shopware\Components\SwagImportExport\DbAdapters\Articles;

use Shopware\Components\SwagImportExport\Exception\AdapterException;
use Shopware\Components\SwagImportExport\Utils\SnippetsHelper;

class RelationWriter
{
    const SIMILAR_TABLE = 's_articles_similar';

    const ACCESSORY_TABLE = 's_articles_relationships';

    /** @var \Enlight_Components_Db_Adapter_Pdo_Mysql */
    protected $db;

    /** @var \Doctrine\DBAL\Connection */
    protected $connection;

    public function __construct()
    {
        $this->db = Shopware()->Db();
        $this->connection = Shopware()->Models()->getConnection();
    }

    public function write($articleId, $accessories, $table)
    {
        if (!in_array($table, array(self::ACCESSORY_TABLE, self::SIMILAR_TABLE))) {
            $message = 'Wrong table is used';
            throw new \Exception($message);
        }
exit;
        $relationships = array();
        foreach ($accessories as $accessory) {
            if ((!isset($accessory['accessoryId']) || !$accessory['accessoryId']) &&
                (!isset($accessory['ordernumber']) || !$accessory['ordernumber'])) {
                continue;
            }

            if (!isset($accessory['accessoryId']) || !$accessory['accessoryId']) {
                $accessoryId = $this->initializeAccessoryId($accessory['ordernumber'], $articleId);
                if (!$accessoryId) {
                    continue;
                }

                $accessory['accessoryId'] = $accessoryId;
            }

            //TODO: if I have to clear all records related with the current '$articleId' I don't need this check
            if ($this->isAccessoryExists($accessory['accessoryId'], $articleId, $table)) {
                continue;
            }

            $relationships[] = $accessory;
        }

        if ($relationships) {
            $this->insertOrUpdateAccessory($relationships, $articleId, $table);
        }
    }

    protected function initializeAccessoryId($orderNumber)
    {
        if (!$accessoryId = $this->getAccessoryIdByOrderNumber($orderNumber)) {
            //TODO: check for unprocessed data
            return;
        }

        return $accessoryId;
    }

    protected function getAccessoryIdByOrderNumber($orderNumber)
    {
        $accessoryId = $this->db->fetchOne(
            'SELECT articleID FROM s_articles_details WHERE ordernumber = ?',
            array($orderNumber)
        );

        return $accessoryId;
    }

    protected function isAccessoryExists($accessoryId, $articleId)
    {
        $isAccessoryExists = $this->db->fetchOne(
            'SELECT id FROM s_articles_relationships WHERE relatedarticle = ? AND articleID = ?',
            array($accessoryId, $articleId)
        );

        return is_numeric($isAccessoryExists);
    }

    private function insertOrUpdateAccessory($accessories, $articleId) {
        $values = implode(
            ', ',
            array_map(
                function ($accessory) use ($articleId) {
                    return "({$articleId}, {$accessory['accessoryId']})";
                },
                $accessories
            )
        );

        $sql = "
            INSERT INTO s_articles_relationships (articleID, relatedarticle)
            VALUES {$values}
            ON DUPLICATE KEY UPDATE articleID=VALUES(articleID), relatedarticle=VALUES(relatedarticle)
        ";

        $this->connection->exec($sql);
    }
}