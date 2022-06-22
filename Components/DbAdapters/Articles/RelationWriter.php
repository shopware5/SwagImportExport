<?php
declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport\Components\DbAdapters\Articles;

use Doctrine\DBAL\Connection;
use Enlight_Components_Db_Adapter_Pdo_Mysql as PDOConnection;
use SwagImportExport\Components\DbAdapters\ArticlesDbAdapter;
use SwagImportExport\Components\Exception\AdapterException;
use SwagImportExport\Components\Utils\SnippetsHelper;

class RelationWriter
{
    protected ArticlesDbAdapter $articlesDbAdapter;

    protected array $relationTypes = [
        'similar',
        'accessory',
    ];

    protected array $relationTables = [
        'accessory' => 's_articles_relationships',
        'similar' => 's_articles_similar',
    ];

    protected string $table;

    protected string $idKey;

    protected string $snippetName;

    protected string $defaultSnippetMessage;

    protected PDOConnection $db;

    protected Connection $connection;

    public function __construct(
        PDOConnection $db,
        Connection $connection
    ) {
        $this->db = $db;
        $this->connection = $connection;
    }

    public function setArticlesDbAdapter(ArticlesDbAdapter $articlesDbAdapter): void
    {
        $this->articlesDbAdapter = $articlesDbAdapter;
    }

    public function getArticlesDbAdapter(): ArticlesDbAdapter
    {
        return $this->articlesDbAdapter;
    }

    /**
     * @throws AdapterException
     */
    public function write(int $articleId, string $mainOrderNumber, array $relations, string $relationType, bool $processedFlag): void
    {
        if (!\is_numeric($articleId)) {
            return;
        }

        $this->initializeRelationData($relationType);

        $newRelations = [];
        $allRelations = [];
        foreach ($relations as $relation) {
            // if relation data has only 'parentIndexElement' element
            if (\count($relation) < 2) {
                break;
            }

            if ((!isset($relation[$this->idKey]) || !$relation[$this->idKey])
                && (!isset($relation['ordernumber']) || !$relation['ordernumber'])
            ) {
                $this->deleteAllRelations($articleId);
                continue;
            }

            if (isset($relation['ordernumber']) && $relation['ordernumber']) {
                $relationId = $this->getRelationIdByOrderNumber($relation['ordernumber']);

                if (!$relationId && $processedFlag === true) {
                    $message = SnippetsHelper::getNamespace()->get($this->snippetName, $this->defaultSnippetMessage);
                    throw new AdapterException(\sprintf($message, $relation['ordernumber']));
                }

                if (!$relationId) {
                    $data = [
                        'articleId' => $mainOrderNumber,
                        'ordernumber' => $relation['ordernumber'],
                    ];

                    $this->getArticlesDbAdapter()->saveUnprocessedData(
                        'articles',
                        \strtolower($relationType),
                        $mainOrderNumber,
                        $data
                    );
                    continue;
                }

                $relation[$this->idKey] = $relationId;
            }

            if (!$this->isRelationIdExists($relation[$this->idKey])) {
                continue;
            }

            if (!$this->isRelationExists($relation[$this->idKey], $articleId)) {
                $newRelations[] = $relation;
            }

            $allRelations[] = $relation;
        }

        if ($allRelations && !$processedFlag) {
            // delete the relations that don't exist in the csv file, but exist in the db"
            $this->deleteRelations($allRelations, $articleId);
        }

        if ($newRelations) {
            $this->insertRelations($newRelations, $articleId); // insert only new relations
        }
    }

    /**
     * Checks whether the relation type exists.
     * Sets the table name.
     * Sets the idKey used to access relation's id. Example: accessory - $relation['accessoryId'],
     * similar - $relation['similarId']
     */
    protected function initializeRelationData(string $relationType): void
    {
        $this->checkRelation($relationType);

        $this->table = $this->relationTables[$relationType];
        $this->idKey = \strtolower($relationType) . 'Id';
        $this->snippetName = 'adapters/articles/' . \strtolower($relationType) . '_not_found';
        $this->defaultSnippetMessage = \ucfirst($relationType) . ' with ordernumber %s does not exists';
    }

    /**
     * Checks whether the relation type exists.
     *
     * @throws \Exception
     */
    protected function checkRelation(string $relationType): void
    {
        if (!\in_array($relationType, $this->relationTypes)) {
            $message = "Wrong relation type is used! Allowed types are: 'accessory' or 'similar'";
            throw new \Exception($message);
        }
    }

    protected function getRelationIdByOrderNumber(string $orderNumber): ?int
    {
        $relationId = $this->db->fetchOne(
            'SELECT articleID FROM s_articles_details WHERE ordernumber = ?',
            [$orderNumber]
        );

        return (int) $relationId ?: null;
    }

    /**
     * Checks whether this article exists.
     */
    protected function isRelationIdExists($relationId): bool
    {
        $articleId = $this->db->fetchOne(
            'SELECT articleID FROM s_articles_details WHERE articleID = ?',
            [$relationId]
        );

        return \is_numeric($articleId);
    }

    /**
     * Checks whether this relation exists.
     */
    protected function isRelationExists($relationId, $articleId): bool
    {
        $isRelationExists = $this->db->fetchOne(
            "SELECT id FROM {$this->table} WHERE relatedarticle = ? AND articleID = ?",
            [$relationId, $articleId]
        );

        return \is_numeric($isRelationExists);
    }

    /**
     * Deletes all relations.
     *
     * @throws \Doctrine\DBAL\DBALException
     */
    private function deleteAllRelations($articleId): void
    {
        $delete = "DELETE FROM {$this->table} WHERE articleID = {$articleId}";
        $this->connection->executeStatement($delete);
    }

    /**
     * Deletes unnecessary relations.
     *
     * @throws \Doctrine\DBAL\DBALException
     */
    private function deleteRelations($relations, $articleId): void
    {
        $relatedIds = \implode(
            ', ',
            \array_map(
                function ($relation) {
                    return $relation[$this->idKey];
                },
                $relations
            )
        );

        $delete = "DELETE FROM {$this->table} WHERE articleID = {$articleId} AND relatedarticle NOT IN ({$relatedIds})";
        $this->connection->executeStatement($delete);
    }

    /**
     * Inserts new relations.
     *
     * @throws \Doctrine\DBAL\DBALException
     */
    private function insertRelations($relations, $articleId): void
    {
        $values = \implode(
            ', ',
            \array_map(
                function ($relation) use ($articleId) {
                    return "({$articleId}, {$relation[$this->idKey]})";
                },
                $relations
            )
        );

        $insert = "INSERT INTO {$this->table} (articleID, relatedarticle) VALUES {$values}";
        $this->connection->executeStatement($insert);
    }
}
