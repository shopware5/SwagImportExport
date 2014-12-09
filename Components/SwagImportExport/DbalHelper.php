<?php

namespace Shopware\Components\SwagImportExport;

use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\ORM\Mapping\ClassMetadata;

class DbalHelper
{

    /** @var \Doctrine\DBAL\Connection  */
    protected $connection;

    public function __construct()
    {
        $this->connection = Shopware()->Models()->getConnection();
    }

    protected function getQueryBuilder()
    {
        return new QueryBuilder($this->connection);
    }

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

            $key = $metaData->fieldMappings[$field]['columnName'];

            $value = $this->getNamedParameter($value, $field, $metaData, $builder);
            if ($primaryId) {
                $builder->set($key, $value);
            } else {
                $builder->setValue($key, $value);
            }
        }

        error_log(print_r($builder->getSQL(), true)."\n", 3, Shopware()->DocPath().'/../error.log');

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
            'decimal' => \PDO::PARAM_INT,
        );

        $nullAble = $metaData->fieldMappings[$key]['nullable'];

        // Check if nullable
        if (empty($value) && $nullAble) {
            return $builder->createNamedParameter(
                "NULL",
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