<?php

namespace Shopware\Components\SwagImportExport\DbAdapters\Articles;

use Shopware\Components\SwagImportExport\Exception\AdapterException;
use Shopware\Components\SwagImportExport\Utils\SnippetsHelper;


/**
 * Class RelationWriter
 * @package Shopware\Components\SwagImportExport\DbAdapters\Articles
 *
 * This writer is used to import 'similar' or 'accessory' articles.
 */
class RelationWriter
{
    protected $relationTypes = array('similar', 'accessory');

    protected $relationTables = array(
        'accessory' => 's_articles_relationships',
        'similar'   => 's_articles_similar'
    );

    protected $table = null;

    protected $idKey = null;

    /** @var \Enlight_Components_Db_Adapter_Pdo_Mysql */
    protected $db;

    /** @var \Doctrine\DBAL\Connection */
    protected $connection;

    public function __construct()
    {
        $this->db = Shopware()->Db();
        $this->connection = Shopware()->Models()->getConnection();
    }

    public function write($articleId, $relations, $relationType)
    {
        $this->initializeRelationData($relationType);

        $newRelations = array();
        $allRelations = array();
        foreach ($relations as $relation) {
            if ((!isset($relation[$this->idKey]) || !$relation[$this->idKey]) &&
                (!isset($relation['ordernumber']) || !$relation['ordernumber'])) {
                $this->deleteAllRelations($articleId);
            }

            //if relationId is missing, find it by orderNumber
            if (!isset($relation[$this->idKey]) || !$relation[$this->idKey]) {
                $relationId = $this->getRelationIdByOrderNumber($relation['ordernumber'], $articleId);
                $relation[$this->idKey] = $relationId;
            }

            //TODO: check whether the given id exists, if not check for unprocessed data
            if (!$this->isRelationIdExists($relation[$this->idKey])) {
                continue;
            }

            if (!$this->isRelationExists($relation[$this->idKey], $articleId)) {
                $newRelations[] = $relation;
            }

            $allRelations[] = $relation;
        }

        if ($allRelations) {
            $this->deleteRelations($allRelations, $articleId); //delete the relations that don't exist in the csv file, but exist in the db"
            $this->insertRelations($newRelations, $articleId); //insert only new relations
        }
    }

    /**
     * Checks whether the relation type exists.
     * Sets the table name.
     * Sets the idKey used to access relation's id. Example: accessory - $relation['accessoryId'], similar - $relation['similarId']
     *
     * @param string $relationType
     * @throws AdapterException
     */
    protected function initializeRelationData($relationType)
    {
        $this->checkRelation($relationType);

        $this->table = $this->relationTables[$relationType];
        $this->idKey = $relationType . 'Id';
    }

    /**
     * Checks whether the relation type exists.
     *
     * @param string $relationType
     * @throws AdapterException
     */
    protected function checkRelation($relationType)
    {
        if (!in_array($relationType, $this->relationTypes)) {
            $message = "Wrong relation type is used! Allowed types are: 'accessory' or 'similar'";
            throw new AdapterException($message);
        }
    }

    /**
     * Gets relation id by orderNumber.
     *
     * @param string $orderNumber
     * @return string
     */
    protected function getRelationIdByOrderNumber($orderNumber)
    {
        $relationId = $this->db->fetchOne(
            'SELECT articleID FROM s_articles_details WHERE ordernumber = ?',
            array($orderNumber)
        );

        return $relationId;
    }

    /**
     * Checks whether this article exists.
     *
     * @param $relationId
     * @return bool
     */
    protected function isRelationIdExists($relationId)
    {
        $articleId = $this->db->fetchOne(
            'SELECT articleID FROM s_articles_details WHERE articleID = ?',
            array($relationId)
        );

        return is_numeric($articleId);
    }

    /**
     * Checks whether this relation exists.
     *
     * @param $relationId
     * @param $articleId
     * @return bool
     */
    protected function isRelationExists($relationId, $articleId)
    {
        $isRelationExists = $this->db->fetchOne(
            "SELECT id FROM {$this->table} WHERE relatedarticle = ? AND articleID = ?",
            array($relationId, $articleId)
        );

        return is_numeric($isRelationExists);
    }

    /**
     * Deletes all relations.
     *
     * @param $articleId
     * @throws \Doctrine\DBAL\DBALException
     */
    private function deleteAllRelations($articleId)
    {
        $delete = "DELETE FROM {$this->table} WHERE articleID = {$articleId}";
        $this->connection->exec($delete);
    }

    /**
     * Deletes unnecessary relations.
     *
     * @param $relations
     * @param $articleId
     * @throws \Doctrine\DBAL\DBALException
     */
    private function deleteRelations($relations, $articleId)
    {
        $relatedIds = implode(
            ', ',
            array_map(
                function ($relation) use ($articleId) {
                    return $relation[$this->idKey];
                },
                $relations
            )
        );

        $delete = "DELETE FROM {$this->table} WHERE articleID = {$articleId} AND relatedarticle NOT IN ({$relatedIds})";
        $this->connection->exec($delete);
    }

    /**
     * Inserts new relations.
     *
     * @param $relations
     * @param $articleId
     * @throws \Doctrine\DBAL\DBALException
     */
    private function insertRelations($relations, $articleId)
    {
        if (!$relations) {
            return;
        }

        $values = implode(
            ', ',
            array_map(
                function ($relation) use ($articleId) {
                    return "({$articleId}, {$relation[$this->idKey]})";
                },
                $relations
            )
        );

        $insert = "INSERT INTO {$this->table} (articleID, relatedarticle) VALUES {$values}";
        var_dump($insert);
        $this->connection->exec($insert);
    }
}