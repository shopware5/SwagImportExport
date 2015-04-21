<?php

namespace Shopware\Components\SwagImportExport;

use Doctrine\ORM\Mapping\ClassMetadata;
use Shopware\Components\SwagImportExport\QueryBuilder\QueryBuilder;

class DbalHelper
{

    /** @var \Doctrine\DBAL\Connection */
    protected $connection;

    /** @var \Doctrine\DBAL\Driver\Statement[]  */
    protected $statements = array();

    public function __construct()
    {
        $this->connection = Shopware()->Models()->getConnection();
    }

    protected function getQueryBuilder()
    {
        return new QueryBuilder($this->connection);
    }

//    public function execute(QueryBuilder $builder)
//    {
//        $builder->execute();return;
//        $hash = md5(serialize($builder->getQueryParts()));
//
//        if ($this->statements[$hash]) {
//            $stmt = $this->statements[$hash];
//        } else {
//            $stmt = $this->connection->prepare($builder->getSql());
//            $this->statements[$hash] =  $stmt;
//        }
//
////        error_log(print_r($builder->getSQL(), true) . "\n", 3, Shopware()->DocPath() . '/../error.log');
////        error_log(print_r($builder->getParameters(), true) . "\n", 3, Shopware()->DocPath() . '/../error.log');
//
//        $stmt->execute($builder->getParameters());
//
//    }

    public function getQueryBuilderForEntity($data, $entity, $primaryId)
    {
        $metaData = Shopware()->Models()->getClassMetadata($entity);
        $table = $metaData->table['name'];

        $builder = $this->getQueryBuilder();
        if ($primaryId) {
            $builder->update($table);
            $builder->where('id = ' . $builder->createNamedParameter($primaryId, \PDO::PARAM_INT));
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
        if (empty($value) && $nullAble) {
            return $builder->createNamedParameter(
                NULL,
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