<?php

namespace Shopware\Components\SwagImportExport\DbAdapters\Articles;

use Shopware\Components\SwagImportExport\DbalHelper;
use Shopware\Components\SwagImportExport\Exception\AdapterException;
use Shopware\Components\SwagImportExport\Utils\SnippetsHelper;

class ConfiguratorWriter
{
    public function __construct()
    {
        $this->dbalHelper = new DbalHelper();
        $this->connection = Shopware()->Models()->getConnection();
        $this->db = Shopware()->Db();
        $this->sets = $this->getSets();
    }

    public function write($articleId, $articleDetailId, $mainDetailId, $configuratorData)
    {
        $configuratorSetId = null;

        foreach ($configuratorData as $configurator) {
            if (!$this->isValid($configurator)) {
                continue;
            }

            /**
             * configurator set
             */
            if (!$configuratorSetId && isset($configurator['configSetId']) && !empty($configurator['configSetId'])) {
                if ($this->checkExistence('s_article_configurator_sets', $configurator['configSetId'])){
                    $configuratorSetId = $configurator['configSetId'];
                }
            }

            if (!$configuratorSetId && isset($configurator['configSetName']) && !empty($configurator['configSetName'])){
                $this->getSet($configurator['configSetName']);
            }

            if (!$configuratorSetId) {
                $configuratorSetId = $this->getSetByArticleId($articleId);
            }

            if (!$configuratorSetId) {
                if (empty($configurator['configSetName'])){
                    $orderNumber = $this->getOrderNumber($articleId);
                    $dataSet['name'] = 'Set-' . $orderNumber;

                } else {
                    $dataSet['name'] = $configurator['configSetName'];
                }

                $dataSet['public'] = false;

                $configuratorSetId = $this->createSet($dataSet);
                $this->sets[$dataSet['name']] = $configuratorSetId;
            }

            if ($mainDetailId != $articleDetailId){
                //update article sets
                $this->updateArticleSetsRelation($articleId, $configuratorSetId);
            }

            /**
             * configurator option
             */
            if (isset($configurator['configOptionId']) && !empty($configurator['configOptionId'])) {
                $optionResult = $this->getOptionRow($configurator['configOptionId']);

                $optionId = $optionResult['id'];
                $groupId = $optionResult['group_id'];

                if (!$optionId) {
                    $message = SnippetsHelper::getNamespace()
                        ->get('adapters/articles/config_option_not_found', 'ConfiguratorOption with id %s not found');
                    throw new AdapterException(sprintf($message, $configurator['configOptionId']));
                }
            } else {
                //gets or creates configurator group
                $groupId = $this->getConfiguratorGroup($configurator);
            }

            $this->updateGroupsRelation($configuratorSetId, $groupId);

            if (isset($configurator['configOptionName']) && !$optionId){
                $optionId = $this->getOption($configurator['configOptionName']);
            }

            //creates option
            if (!$optionId) {
                if (isset($configurator['configOptionPosition']) && !empty($configurator['configOptionPosition'])){
                    $position = $configurator['configOptionPosition'];
                } else {
                    $position = 1;
                }

                $dataOption = array(
                    'groupId' => $groupId,
                    'name' => $configurator['configOptionName'],
                    'position' => $position
                );

                $optionId = $this->createOption($dataOption);
            }

            $this->updateOptionRelation($articleDetailId, $optionId);
            $this->updateSetOptionRelation($configuratorSetId, $optionId);
        }
    }

    private function isValid($configurator)
    {
        if (!isset($configurator['configOptionId']) || empty($configurator['configOptionId'])){

            if (!isset($configurator['configGroupName']) && !isset($configurator['configGroupId'])) {
                return false;
            }

            if (empty($configurator['configGroupName']) && empty($configurator['configGroupId'])) {
                return false;
            }

            if (!isset($configurator['configOptionName']) || empty($configurator['configOptionName'])) {
                return false;
            }
        }

        return true;
    }

    protected function updateArticleSetsRelation($articleId, $setId)
    {
        $this->db->query('UPDATE s_articles SET configurator_set_id = ? WHERE id = ?', array($setId, $articleId));
    }

    protected function updateGroupsRelation($setId, $groupId)
    {
        $sql = "
            INSERT INTO s_article_configurator_set_group_relations (set_id, group_id)
            VALUES ($setId, $groupId)
            ON DUPLICATE KEY UPDATE set_id=VALUES(set_id), group_id=VALUES(group_id)
        ";

        $this->connection->exec($sql);
    }

    protected function updateOptionRelation($articleDetailId, $optionId)
    {
        $sql = "
            INSERT INTO s_article_configurator_option_relations (article_id, option_id)
            VALUES ($articleDetailId, $optionId)
            ON DUPLICATE KEY UPDATE article_id=VALUES(article_id), option_id=VALUES(option_id)
        ";

        $this->connection->exec($sql);
    }

    protected function updateSetOptionRelation($setId, $optionId)
    {
        $sql = "
            INSERT INTO s_article_configurator_set_option_relations (set_id, option_id)
            VALUES ($setId, $optionId)
            ON DUPLICATE KEY UPDATE set_id=VALUES(set_id), option_id=VALUES(option_id)
        ";

        $this->connection->exec($sql);
    }

    protected function getSets()
    {
        $sets = array();
        $result = $this->connection->fetchAll('SELECT `id`, `name` FROM s_article_configurator_sets');

        foreach ($result as $row) {
            $sets[$row['name']] = $row['id'];
        }

        return $sets;
    }

    protected function getSetByArticleId($articleId)
    {
        $result = $this->db->fetchRow(
            'SELECT configurator_set_id FROM s_articles WHERE id = ?',
            array($articleId)
        );

        return $result['configurator_set_id'];
    }

    protected function createSet($data)
    {
        $builder =  $this->dbalHelper->getQueryBuilderForEntity(
            $data,
            'Shopware\Models\Article\Configurator\Set',
            false
        );
        $builder->execute();

        return $this->connection->lastInsertId();
    }

    protected function createGroup($data)
    {
        $builder =  $this->dbalHelper->getQueryBuilderForEntity(
            $data,
            'Shopware\Models\Article\Configurator\Group',
            false
        );
        $builder->execute();

        return $this->connection->lastInsertId();
    }

    protected function createOption($data)
    {
        $builder =  $this->dbalHelper->getQueryBuilderForEntity(
            $data,
            'Shopware\Models\Article\Configurator\Option',
            false
        );
        $builder->execute();

        return $this->connection->lastInsertId();
    }

    protected function getOrderNumber($articleId)
    {
        return $this->connection->fetchColumn(
            "SELECT `ordernumber` FROM s_articles_details
             WHERE kind = 1 AND articleID = ?",
            array($articleId)
        );
    }

    /**
     * Returns the supplier ID
     *
     * @param $name
     * @return int
     */
    public function getSet($name)
    {
        $setId = $this->sets[$name];

        return $setId;
    }

    public function getGroup($name)
    {
        $result = $this->connection->fetchColumn(
            "SELECT `id` FROM s_article_configurator_group WHERE `name` = ?",
            array($name)
        );

        return $result;
    }

    public function getOption($name)
    {
        $result = $this->db->fetchRow(
            'SELECT `id` FROM s_article_configurator_options WHERE `name` = ?',
            array($name)
        );

        return $result['id'];
    }

    public function getOptionRow($id)
    {
        return $this->db->fetchRow(
            'SELECT `id`, `group_id`, `name`, `position` FROM s_article_configurator_options WHERE `id` = ?',
            array($id)
        );
    }

    public function checkExistence($table, $id)
    {
        $result = $this->connection->fetchColumn(
            "SELECT `id` FROM $table WHERE id = ?",
            array($id)
        );

        return $result ? true : false;
    }


    public function getConfiguratorGroup($data)
    {
        $groupPosition = 0;

        if (isset($data['configGroupId'])) {
            if ($this->checkExistence('s_article_configurator_groups', $data['configGroupId'])){
                $groupId = $data['configGroupId'];
            }
        }

        if (isset($data['configGroupName']) && !$groupId) {
            $groupId = $this->getGroup($data['configGroupName']);

            if (!$groupId) {
                $groupData = array(
                    'name' => $data['configGroupName'],
                    'option' => $groupPosition
                );

                $groupId = $this->createGroup($groupData);
                $this->groups[$groupData['name']] = $groupId;
            }
        }

        if (!$groupId) {
            $message = SnippetsHelper::getNamespace()
                ->get('adapters/articles/provide_groupname_groupid', 'Please provide groupname or groupId');
            throw new AdapterException($message);
        }

        return $groupId;
    }
}