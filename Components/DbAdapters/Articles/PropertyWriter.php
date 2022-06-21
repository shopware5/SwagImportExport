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
use Shopware\Models\Property\Group;
use Shopware\Models\Property\Option;
use Shopware\Models\Property\Value;
use SwagImportExport\Components\DbalHelper;
use SwagImportExport\Components\Exception\AdapterException;
use SwagImportExport\Components\Utils\SnippetsHelper;

/**
 * Writer for the s_filter table and all relations.
 */
class PropertyWriter
{
    private DbalHelper $dbalHelper;

    private Connection $connection;

    private PDOConnection $db;

    private SnippetsHelper $snippetsHelper;

    public function __construct(
        DbalHelper $dbalHelper,
        Connection $connection,
        PDOConnection $db,
        SnippetsHelper $snippetsHelper
    ) {
        $this->dbalHelper = $dbalHelper;
        $this->connection = $connection;
        $this->db = $db;
        $this->snippetsHelper = $snippetsHelper;
    }

    /**
     * @param array<string|int, array<string, int|string>>|null $propertiesData
     *
     * @throws AdapterException
     */
    public function writeUpdateCreatePropertyGroupsFilterAndValues(int $articleId, string $orderNumber, ?array $propertiesData): void
    {
        if (!$propertiesData) {
            return;
        }
        $optionRelationInsertStatements = [];
        $valueRelationInsertStatements = [];

        foreach ($propertiesData as $propertyData) {
            $filterGroupId = $this->findCreateOrUpdateGroup($articleId, $propertyData);
            if (!$filterGroupId) {
                continue;
            }

            /*
             * Only update relations if value and option id were passed.
             */
            if (isset($propertyData['propertyValueId']) && !empty($propertyData['propertyValueId'])) {
                $valueId = $propertyData['propertyValueId'];
                $optionId = $this->getOptionByValueId((int) $valueId);

                if (!$optionId) {
                    $message = $this->snippetsHelper->getNamespace()
                        ->get('adapters/articles/property_id_not_found', 'Property value by id %s not found for article %s');
                    throw new AdapterException(\sprintf($message, $valueId, $orderNumber));
                }

                $optionRelationInsertStatements[] = "($optionId, $filterGroupId)";
                $valueRelationInsertStatements[] = "($valueId, $articleId)";
                continue;
            }

            /*
             * Update or create options by value name
             */
            if (isset($propertyData['propertyValueName']) && !empty($propertyData['propertyValueName'])) {
                [$optionId, $valueId] = $this->updateOrCreateOptionAndValuesByValueName($orderNumber, $propertyData);

                $optionRelationInsertStatements[] = "($optionId, $filterGroupId)";
                $valueRelationInsertStatements[] = "($valueId, $articleId)";
                continue;
            }
        }

        if ($optionRelationInsertStatements) {
            $this->insertOrUpdateOptionRelations($optionRelationInsertStatements);
        }

        if ($valueRelationInsertStatements) {
            $this->insertOrUpdateValueRelations($valueRelationInsertStatements);
        }
    }

    /**
     * @param array<string, mixed> $data
     * @param class-string         $entityName
     */
    private function createElement(string $entityName, array $data): int
    {
        $builder = $this->dbalHelper->getQueryBuilderForEntity(
            $data,
            $entityName,
            null
        );
        $builder->execute();

        return (int) $this->connection->lastInsertId();
    }

    /**
     * @param array<string> $relations
     *
     * Updates/Creates relation between property group and property option
     */
    private function insertOrUpdateOptionRelations(array $relations): void
    {
        $values = \implode(',', $relations);

        $sql = "
            INSERT INTO s_filter_relations (optionID, groupID)
            VALUES $values
            ON DUPLICATE KEY UPDATE groupID=VALUES(groupID), optionID=VALUES(optionID)
        ";

        $this->connection->exec($sql);
    }

    /**
     * @param array<string> $relations
     *
     * Updates/Creates relation between articles and property values
     */
    private function insertOrUpdateValueRelations(array $relations): void
    {
        $values = \implode(',', $relations);

        $sql = "
            INSERT INTO s_filter_articles (valueID, articleID)
            VALUES $values
            ON DUPLICATE KEY UPDATE articleID=VALUES(articleID), valueID=VALUES(valueID)
        ";

        $this->connection->exec($sql);
    }

    /**
     * Updates/Creates relation between articles and property groups
     */
    private function updateGroupsRelation(int $filterGroupId, int $articleId): void
    {
        $this->db->query('UPDATE s_articles SET filtergroupID = ? WHERE id = ?', [$filterGroupId, $articleId]);
    }

    /**
     * @return array<string, string>
     */
    private function getFilterGroups(): array
    {
        return $this->db->fetchPairs('SELECT `name`, `id` FROM s_filter');
    }

    private function getFilterGroupIdByNameFromCacheProperty(string $name): ?int
    {
        return ((int) $this->getFilterGroups()[$name]) ?? null;
    }

    /**
     * @return array<string, string>
     */
    private function getOptions(): array
    {
        return $this->db->fetchPairs('SELECT `name`, `id` FROM s_filter_options');
    }

    /**
     * Returns the id of an option
     */
    private function getOptionByName(string $name): int
    {
        return (int) $this->db->fetchOne('SELECT `id` FROM s_filter_options WHERE `name` = ?', $name);
    }

    private function getValue(string $name, int $filterGroupId): int
    {
        return (int) $this->connection->fetchOne(
            'SELECT `id` FROM s_filter_values
             WHERE `optionID` = ? AND `value` = ?',
            [$filterGroupId, $name]
        );
    }

    private function getGroupFromArticle(int $articleId): int
    {
        return (int) $this->connection->fetchOne(
            'SELECT `filtergroupID` FROM s_articles
             INNER JOIN s_filter ON s_articles.filtergroupID = s_filter.id
             WHERE s_articles.id = ?',
            [$articleId]
        );
    }

    private function getOptionByValueId(int $valueId): int
    {
        return (int) $this->connection->fetchOne('SELECT `optionID` FROM s_filter_values WHERE id = ?', [$valueId]);
    }

    /**
     * @param array<string, mixed> $propertyData
     */
    private function createOption(string $optionName, array $propertyData): int
    {
        $optionData = [
            'name' => $optionName,
            'filterable' => !empty($propertyData['propertyOptionFilterable']) ? 1 : 0,
        ];

        return $this->createElement(Option::class, $optionData);
    }

    /**
     * @param array<string, mixed> $propertyData
     */
    private function createValue(array $propertyData, string $valueName, int $optionId): int
    {
        $position = !empty($propertyData['propertyValuePosition']) ? $propertyData['propertyValuePosition'] : 0;

        $valueData = [
            'value' => $valueName,
            'optionId' => $optionId,
            'position' => $position,
        ];

        return $this->createElement(Value::class, $valueData);
    }

    private function createGroup(string $groupName): int
    {
        $groupData = [
            'name' => $groupName,
        ];

        return $this->createElement(Group::class, $groupData);
    }

    /**
     * @param array<string, mixed> $propertyData
     */
    private function findCreateOrUpdateGroup(int $articleId, array $propertyData): int
    {
        $filterGroupId = $this->getGroupFromArticle($articleId);

        if (!$filterGroupId && $propertyData['propertyGroupName']) {
            $filterGroupName = $propertyData['propertyGroupName'];
            $filterGroupId = $this->getFilterGroupIdByNameFromCacheProperty($filterGroupName);

            if (!$filterGroupId) {
                $filterGroupId = $this->createGroup($filterGroupName);
            }

            $this->updateGroupsRelation($filterGroupId, $articleId);
        }

        return $filterGroupId;
    }

    /**
     * @param array<string, mixed> $propertyData
     *
     * @throws AdapterException
     */
    private function updateOrCreateOptionAndValuesByValueName(string $orderNumber, array $propertyData): array
    {
        if (isset($propertyData['propertyOptionId']) && !empty($propertyData['propertyOptionId'])) {
            // todo: check  propertyOptionId existence
            $optionId = $propertyData['propertyOptionId'];
        } elseif (isset($propertyData['propertyOptionName']) && !empty($propertyData['propertyOptionName'])) {
            $optionName = $propertyData['propertyOptionName'];
            $optionId = $this->getOptionByName($optionName);

            if (!$optionId) {
                $optionId = $this->createOption($optionName, $propertyData);
            }
        } else {
            $message = $this->snippetsHelper->getNamespace()
                ->get('adapters/articles/property_option_required', '');
            throw new AdapterException(\sprintf($message, $orderNumber));
        }

        $valueName = $propertyData['propertyValueName'];
        $valueId = $this->getValue($valueName, $optionId);

        if (!$valueId) {
            $valueId = $this->createValue($propertyData, $valueName, $optionId);
        }

        return [$optionId, $valueId];
    }
}
