<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport\Components;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\ORM\Mapping\ClassMetadata;
use Enlight_Event_EventManager as EventManager;
use Shopware\Components\Model\ModelManager;

class DbalHelper
{
    protected Connection $connection;

    private ModelManager $modelManager;

    private EventManager $eventManager;

    public function __construct(Connection $connection, ModelManager $modelManager, EventManager $eventManager)
    {
        $this->connection = $connection;
        $this->modelManager = $modelManager;
        $this->eventManager = $eventManager;
    }

    /**
     * @param array<string, mixed> $data
     * @param class-string         $entity
     */
    public function getQueryBuilderForEntity(array $data, string $entity, ?int $primaryId): QueryBuilder
    {
        $metaData = $this->modelManager->getClassMetadata($entity);
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
            if (!\array_key_exists($field, $metaData->fieldMappings)) {
                continue;
            }

            $value = $this->eventManager->filter(
                'Shopware_Components_SwagImportExport_DbalHelper_GetQueryBuilderForEntity_Value',
                $value,
                [
                    'subject' => $this,
                    'field' => $field,
                    'entity' => $entity,
                    'data' => $data,
                ]
            );

            if (!\array_key_exists('columnName', $metaData->fieldMappings[$field])) {
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

    protected function getQueryBuilder(): QueryBuilder
    {
        return new QueryBuilder($this->connection);
    }

    protected function getNamedParameter(?string $value, string $key, ClassMetadata $metaData, QueryBuilder $builder): string
    {
        $pdoTypeMapping = [
            'string' => \PDO::PARAM_STR,
            'text' => \PDO::PARAM_STR,
            'date' => \PDO::PARAM_STR,
            'datetime' => \PDO::PARAM_STR,
            'boolean' => \PDO::PARAM_INT,
            'integer' => \PDO::PARAM_INT,
            'decimal' => \PDO::PARAM_STR,
            'float' => \PDO::PARAM_STR,
        ];

        $nullAble = \array_key_exists('nullable', $metaData->fieldMappings[$key]) && $metaData->fieldMappings[$key]['nullable'];

        // Check if nullable
        if (!isset($value) && $nullAble) {
            return $builder->createNamedParameter(null, \PDO::PARAM_NULL);
        }

        $type = $metaData->fieldMappings[$key]['type'];
        if (!\array_key_exists($type, $pdoTypeMapping)) {
            throw new \RuntimeException(\sprintf('Type %s not found', $type));
        }

        return $builder->createNamedParameter($value, $pdoTypeMapping[$type]);
    }
}
