<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Shopware\Components\SwagImportExport;

use Doctrine\ORM\Mapping\ClassMetadata;
use Shopware\Components\SwagImportExport\QueryBuilder\QueryBuilder;

class DbalHelper
{
    /** @var \Doctrine\DBAL\Connection */
    protected $connection;

    /** @var \Doctrine\DBAL\Driver\Statement[] */
    protected $statements = array();

    public function __construct()
    {
        $this->connection = Shopware()->Models()->getConnection();
    }

    /**
     * @return QueryBuilder
     */
    protected function getQueryBuilder()
    {
        return new QueryBuilder($this->connection);
    }

    /**
     * @param $data
     * @param $entity
     * @param $primaryId
     * @return QueryBuilder
     */
    public function getQueryBuilderForEntity($data, $entity, $primaryId)
    {
        $metaData = Shopware()->Models()->getClassMetadata($entity);
        $table = $metaData->table['name'];

        $builder = $this->getQueryBuilder();
        if ($primaryId) {
            $id = $builder->createNamedParameter($primaryId, \PDO::PARAM_INT);
            $builder->update($table);
            //update article id in case we don't have any field for update
            $builder->set('id', $id);
            $builder->where('id = ' . $id);
        } else {
            $builder->insert($table);
        }

        foreach ($data as $field => $value) {
            if (!array_key_exists($field, $metaData->fieldMappings)) {
                continue;
            }

            $key = $this->connection->quoteIdentifier($metaData->fieldMappings[$field]['columnName']);

            $value = $this->getNamedParameter($value, $field, $metaData, $builder);
            if ($primaryId) {
                $builder->set($key, $value);
            } else {
                $builder->setValue($key, $value);
            }
        }

        return $builder;
    }

    /**
     * @param $value
     * @param $key
     * @param ClassMetadata $metaData
     * @param QueryBuilder $builder
     * @return string
     */
    protected function getNamedParameter($value, $key, ClassMetadata $metaData, QueryBuilder $builder)
    {
        $pdoTypeMapping = array(
            'string' => \PDO::PARAM_STR,
            'text' => \PDO::PARAM_STR,
            'date' => \PDO::PARAM_STR,
            'datetime' => \PDO::PARAM_STR,
            'boolean' => \PDO::PARAM_INT,
            'integer' => \PDO::PARAM_INT,
            'decimal' => \PDO::PARAM_STR,
            'float' => \PDO::PARAM_STR,
        );

        $nullAble = $metaData->fieldMappings[$key]['nullable'];

        // Check if nullable
        if (!isset($value) && $nullAble) {
            return $builder->createNamedParameter(
                null,
                \PDO::PARAM_NULL
            );
        }

        $type = $metaData->fieldMappings[$key]['type'];
        if (!array_key_exists($type, $pdoTypeMapping)) {
            throw new \RuntimeException("Type {$type} not found");
        }

        return $builder->createNamedParameter(
            $value,
            $pdoTypeMapping[$type]
        );
    }
}
