<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Shopware\Components\SwagImportExport\DbAdapters\Articles;

use Doctrine\DBAL\Connection;
use Enlight_Components_Db_Adapter_Pdo_Mysql as PDOConnection;
use Shopware\Components\SwagImportExport\DbalHelper;
use Shopware\Components\SwagImportExport\Exception\AdapterException;
use Shopware\Components\SwagImportExport\Utils\SnippetsHelper;
use Shopware\Models\Property\Group;
use Shopware\Models\Property\Option;
use Shopware\Models\Property\Value;

/**
 * Writer for the s_filter table and all relations.
 */
class PropertyWriter
{
    /**
     * @var DbalHelper
     */
    private $dbalHelper;

    /**
     * @var Connection
     */
    private $connection;

    /**
     * @var PDOConnection
     */
    private $db;

    /**
     * @var array<string, int>
     */
    private $groups;

    /**
     * @var array
     */
    private $options;

    /**
     * @var SnippetsHelper
     */
    private $snippetsHelper;

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

        $this->groups = $this->getFilterGroups();
        $this->options = $this->getOptions();
    }

    /**
     * @return PropertyWriter
     */
    public static function createFromGlobalSingleton()
    {
        return new PropertyWriter(
            DbalHelper::create(),
            Shopware()->Container()->get('dbal_connection'),
            Shopware()->Container()->get('db'),
            new SnippetsHelper()
        );
    }

    /**
     * @param int    $articleId
     * @param string $orderNumber
     * @param array  $propertiesData
     *
     * @throws AdapterException
     */
    public function writeUpdateCreatePropertyGroupsFilterAndValues($articleId, $orderNumber, $propertiesData)
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
                $optionId = $this->getOptionByValueId($valueId);

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
                list($optionId, $valueId) = $this->updateOrCreateOptionAndValuesByValueName($orderNumber, $propertyData);

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
     * @param class-string $entityName
     */
    private function createElement(string $entityName, array $data): int
    {
        $builder = $this->dbalHelper->getQueryBuilderForEntity(
            $data,
            $entityName,
            false
        );
        $builder->execute();

        return (int) $this->connection->lastInsertId();
    }

    /**
     * Updates/Creates relation between property group and property option
     */
    private function insertOrUpdateOptionRelations(array $relations)
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
     * Updates/Creates relation between articles and property values
     */
    private function insertOrUpdateValueRelations(array $relations)
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
     *
     * @param int $filterGroupId
     * @param int $articleId
     */
    private function updateGroupsRelation($filterGroupId, $articleId): void
    {
        $this->db->query('UPDATE s_articles SET filtergroupID = ? WHERE id = ?', [$filterGroupId, $articleId]);
    }

    /**
     * @return array
     */
    private function getFilterGroups()
    {
        return $this->db->fetchPairs('SELECT `name`, `id` FROM s_filter');
    }

    /**
     * @param string $name
     *
     * @return int|null
     */
    private function getFilterGroupIdByNameFromCacheProperty($name)
    {
        return $this->groups[$name] ?? null;
    }

    /**
     * @return array
     */
    private function getOptions()
    {
        return $this->db->fetchPairs('SELECT `name`, `id` FROM s_filter_options');
    }

    /**
     * Returns the id of an option
     *
     * @param string $name
     *
     * @return int
     */
    private function getOptionByName($name)
    {
        return $this->options[$name];
    }

    /**
     * @param string $name
     * @param int    $filterGroupId
     *
     * @return string|bool
     */
    private function getValue($name, $filterGroupId)
    {
        return $this->connection->fetchColumn(
            'SELECT `id` FROM s_filter_values
             WHERE `optionID` = ? AND `value` = ?',
            [$filterGroupId, $name]
        );
    }

    /**
     * @param string|int $articleId
     */
    private function getGroupFromArticle($articleId): int
    {
        return (int) $this->connection->fetchColumn(
            'SELECT `filtergroupID` FROM s_articles
             INNER JOIN s_filter ON s_articles.filtergroupID = s_filter.id
             WHERE s_articles.id = ?',
            [$articleId]
        );
    }

    /**
     * @param string|int $valueId
     *
     * @return string|bool
     */
    private function getOptionByValueId($valueId)
    {
        return $this->connection->fetchColumn('SELECT `optionID` FROM s_filter_values WHERE id = ?', [$valueId]);
    }

    /**
     * @param string $optionName
     * @param array  $propertyData
     *
     * @return int
     */
    private function createOption($optionName, $propertyData)
    {
        $optionData = [
            'name' => $optionName,
            'filterable' => !empty($propertyData['propertyOptionFilterable']) ? 1 : 0,
        ];

        $this->options[$optionName] = $this->createElement(Option::class, $optionData);

        return $this->options[$optionName];
    }

    /**
     * @param array  $propertyData
     * @param string $valueName
     * @param int    $optionId
     */
    private function createValue($propertyData, $valueName, $optionId): int
    {
        $position = !empty($propertyData['propertyValuePosition']) ? $propertyData['propertyValuePosition'] : 0;

        $valueData = [
            'value' => $valueName,
            'optionId' => $optionId,
            'position' => $position,
        ];

        return $this->createElement(Value::class, $valueData);
    }

    /**
     * @param string $groupName
     */
    private function createGroup($groupName): int
    {
        $groupData = [
            'name' => $groupName,
        ];

        $groupId = $this->createElement(Group::class, $groupData);
        $this->groups[$groupName] = $groupId;

        return $groupId;
    }

    /**
     * @param int   $articleId
     * @param array $propertyData
     *
     * @return int
     */
    private function findCreateOrUpdateGroup($articleId, $propertyData)
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
     * @param string $orderNumber
     * @param array  $propertyData
     *
     * @throws AdapterException
     *
     * @return array
     */
    private function updateOrCreateOptionAndValuesByValueName($orderNumber, $propertyData)
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
