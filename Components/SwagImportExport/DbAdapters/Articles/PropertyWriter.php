<?php

namespace Shopware\Components\SwagImportExport\DbAdapters\Articles;

use Shopware\Components\SwagImportExport\DbalHelper;
use Shopware\Components\SwagImportExport\Exception\AdapterException;
use Shopware\Components\SwagImportExport\Utils\SnippetsHelper;

class PropertyWriter
{
    public function __construct()
    {
        $this->dbalHelper = new DbalHelper();
        $this->connection = Shopware()->Models()->getConnection();
        $this->db = Shopware()->Db();
        $this->groups = $this->getGroups();
        $this->options = $this->getOptions();
    }

    public function write($articleId, $orderNumber, $propertiesData)
    {
        foreach ($propertiesData as $index => $propertyData) {

            if (!$this->isValid($propertyData)) {
                continue;
            }

            //gets group id from article
            $groupId = $this->getGroupFromArticle($articleId);

            if (!$groupId && $propertyData['propertyGroupName']){
                $groupName = $propertyData['propertyGroupName'];
                $groupId = $this->getGroup($groupName);

                if (!$groupId){
                    //creates groups
                    $groupData = array(
                        'name' => $groupName
                    );
                    $groupId = $this->createElement('Shopware\Models\Property\Group', $groupData);
                    $this->groups[$groupName] = $groupId;

                    $this->updateGroupsRelation($groupId, $articleId);
                }
            }

            if (!$groupId) {
                $message = SnippetsHelper::getNamespace()
                    ->get('adapters/articles/property_group_name_not_found', 'There is no propertyGroupName specified for article %s');
                throw new AdapterException(sprintf($message, $orderNumber));
            }

            if (isset($propertyData['propertyValueId']) && !empty($propertyData['propertyValueId'])){
                
                //todo find value by Id

            } else if (isset($propertyData['propertyValueName']) && !empty($propertyData['propertyValueName'])){
                if (isset($propertyData['propertyOptionId']) && !empty($propertyData['propertyOptionId'])) {
                    //todo: check  propertyOptionId existence
                    $optionId = $propertyData['propertyOptionId'];
                } elseif (isset($propertyData['propertyOptionName']) && !empty($propertyData['propertyOptionName'])) {
                    $optionName = $propertyData['propertyOptionName'];
                    $optionId = $this->getOption($optionName);

                    if (!$optionId){
                        //creates option
                        $optionData = array(
                            'name' => $optionName,
                            'filterable' => !empty($valueData['propertyOptionFilterable']) ? 1 : 0
                        );
                        $optionId = $this->createElement('Shopware\Models\Property\Option', $optionData);

                        //updates property group mapper
                        $this->options[$optionName] = $optionId;
                        $optionRelations[] = "($optionId, $groupId)";
                    }
                } else {
                    $message = SnippetsHelper::getNamespace()
                        ->get('adapters/articles/property_option_required', 'A property option need to be given for each property value for article %s');
                    throw new AdapterException(sprintf($message, $orderNumber));
                }

                $valueName = $propertyData['propertyValueName'];
                $valueId = $this->getValue($valueName, $optionId);

                if (!$valueId){
                    $position = !empty($propertyData['propertyValuePosition']) ? $propertyData['propertyValuePosition'] : 0;
                    $numeric = !empty($propertyData['propertyValueNumeric']) ? $propertyData['propertyValueNumeric'] : 0;

                    $valueData = array(
                        'value' => $valueName,
                        'optionId' => $optionId,
                        'position' => $position,
                        'valueNumeric' => $numeric
                    );

                    $valueId = $this->createElement('Shopware\Models\Property\Value', $valueData);
                }

            } else {
                $message = SnippetsHelper::getNamespace()
                    ->get('adapters/articles/property_id_or_name_required', 'Article %s requires name or id for property value');
                throw new AdapterException(sprintf($message, $orderNumber));
            }

            $valueRelations[] = "($valueId, $articleId)";
        }

        if($optionRelations){
            $this->optionsRelation($optionRelations);
        }

        if($valueRelations){
            $this->valuesRelation($valueRelations);
        }
    }

    /**
     * @param $data
     * @return bool
     */
    private function isValid($data)
    {
        if (!isset($data['propertyGroupName']) && empty($data['propertyGroupName'])){
            return false;
        }

        if (empty($data['propertyValueName']) && empty($data['propertyValueId'])){
            return false;
        }

        return true;
    }

    private function createElement($entityName, $data)
    {
        $builder =  $this->dbalHelper->getQueryBuilderForEntity(
            $data,
            $entityName,
            false
        );
        $builder->execute();

        return $this->connection->lastInsertId();
    }

    /**
     * Updates/Creates relation between property group and property option
     * @param array $relations
     */
    private function optionsRelation(array $relations)
    {
        $values = implode(',', $relations);

        $sql = "
            INSERT INTO s_filter_relations (optionID, groupID)
            VALUES $values
            ON DUPLICATE KEY UPDATE groupID=VALUES(groupID), optionID=VALUES(optionID)
        ";

        $this->connection->exec($sql);
    }

    /**
     * Updates/Creates relation between articles and property values
     * @param array $relations
     */
    private function valuesRelation(array $relations)
    {
        $values = implode(',', $relations);

        $sql = "
            INSERT INTO s_filter_articles (valueID, articleID)
            VALUES $values
            ON DUPLICATE KEY UPDATE articleID=VALUES(articleID), valueID=VALUES(valueID)
        ";

        $this->connection->exec($sql);
    }

    /**
     * Updates/Creates relation between articles and property groups
     * @param $groupId
     * @param $articleId
     */
    private function updateGroupsRelation($groupId, $articleId)
    {
        $this->db->query('UPDATE s_articles SET filtergroupID = ? WHERE id = ?', array($groupId, $articleId));
    }

    public function getGroups()
    {
        $groups = array();
        $result = $this->connection->fetchAll('SELECT `id`, `name` FROM s_filter');

        foreach ($result as $row) {
            $groups[$row['name']] = $row['id'];
        }

        return $groups;
    }

    public function getGroup($name)
    {
        $groupId = $this->groups[$name];

        return $groupId;
    }

    public function getOptions()
    {
        $options = array();
        $result = $this->connection->fetchAll('SELECT `id`, `name` FROM s_filter_options');

        foreach ($result as $row) {
            $options[$row['name']] = $row['id'];
        }

        return $options;
    }

    public function getOption($name)
    {
        $optionId = $this->options[$name];

        return $optionId;
    }

    public function getValue($name, $groupId)
    {
        return $this->connection->fetchColumn(
            "SELECT `id` FROM s_filter_values
             WHERE `optionID` = ? AND `value` = ?",
            array($groupId, $name)
        );
    }

    public function getGroupFromArticle($articleId)
    {
        return $this->connection->fetchColumn(
            "SELECT `filtergroupID` FROM s_articles
             WHERE id = ?",
            array($articleId)
        );
    }



}