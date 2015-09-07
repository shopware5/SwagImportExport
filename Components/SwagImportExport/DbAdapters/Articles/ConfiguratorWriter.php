<?php

namespace Shopware\Components\SwagImportExport\DbAdapters\Articles;

use Shopware\Components\SwagImportExport\DbalHelper;
use Shopware\Components\SwagImportExport\Exception\AdapterException;
use Shopware\Components\SwagImportExport\Utils\SnippetsHelper;
use Shopware\Components\SwagImportExport\Validators\Articles\ConfiguratorValidator;

class ConfiguratorWriter
{
    /** @var ConfiguratorValidator */
    protected $validator = null;

    public function __construct()
    {
        $this->dbalHelper = new DbalHelper();
        $this->connection = Shopware()->Models()->getConnection();
        $this->db = Shopware()->Db();
        $this->sets = $this->getSets();
        $this->validator = new ConfiguratorValidator();
    }

    public function write($articleId, $articleDetailId, $mainDetailId, $configuratorData)
    {
        $configuratorSetId = null;

        foreach ($configuratorData as $configurator) {
            if (!$this->isValid($configurator)) {
                continue;
            }
            $configurator = $this->validator->prepareInitialData($configurator);
            $this->validator->validate($configurator, ConfiguratorValidator::$mapper);

            /**
             * configurator set
             */
            if (!$configuratorSetId && isset($configurator['configSetId']) && !empty($configurator['configSetId'])) {
                $setExists = $this->checkExistence('s_article_configurator_sets', $configurator['configSetId']);
                $match = $this->compareSetIdByName($articleId, $configurator['configSetId']);
                if ($setExists && $match){
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

                if (array_key_exists($dataSet['name'], $this->sets)) {
                    $configuratorSetId = $this->sets[$dataSet['name']];
                } else {
                    $configuratorSetId = $this->createSet($dataSet);
                    $this->sets[$dataSet['name']] = $configuratorSetId;
                }
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
                $optionId = $this->getOptionId($configurator['configOptionName'], $groupId);
            }

            //creates option
            if (!$optionId) {
                if (isset($configurator['configOptionPosition']) && !empty($configurator['configOptionPosition'])){
                    $position = $configurator['configOptionPosition'];
                } else {
                    $position = $this->getNextOptionPosition($groupId);
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

            unset($groupId);
            unset($optionId);
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
            "SELECT `id` FROM s_article_configurator_groups WHERE `name` = ?",
            array($name)
        );

        return $result;
    }

    public function getOptionId($optionName, $groupId)
    {
        $optionId = $this->db->fetchOne(
            'SELECT `id` FROM s_article_configurator_options WHERE `name` = ? AND `group_id` = ?',
            array($optionName, $groupId)
        );

        return $optionId;
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
        if (isset($data['configGroupId'])) {
            if ($this->checkExistence('s_article_configurator_groups', $data['configGroupId'])){
                $groupId = $data['configGroupId'];
            }
        }

        if (isset($data['configGroupName']) && !$groupId) {
            $groupId = $this->getGroup($data['configGroupName']);

            if (!$groupId) {
                $groupPosition = $this->getNextGroupPosition();
                $groupData = array(
                    'name' => $data['configGroupName'],
                    'position' => $groupPosition
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

    protected function getNextGroupPosition()
    {
        $position = $this->db->fetchOne("SELECT `position` FROM `s_article_configurator_groups` ORDER BY `position` DESC LIMIT 1");
        $position = $position ? ++$position : 1;

        return $position;
    }

    protected function getNextOptionPosition($groupId)
    {
        $position = $this->db->fetchOne(
            "SELECT `position` FROM `s_article_configurator_options` WHERE `group_id` = ? ORDER BY `position` DESC LIMIT 1",
            $groupId
        );
        $position = $position ? ++$position : 1;

        return $position;
    }

    /**
     * Compares the given setId from the import file by name
     *
     * @param $articleId
     * @param $setId
     * @return bool
     */
    protected function compareSetIdByName($articleId, $setId)
    {
        $setName = 'Set-' . $this->getOrderNumber($articleId);

        return $this->getSet($setName) == $setId;
    }
}