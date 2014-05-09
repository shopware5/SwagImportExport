<?php

/**
 * Shopware 4
 * Copyright © shopware AG
 *
 * According to our dual licensing model, this program can be used either
 * under the terms of the GNU Affero General Public License, version 3,
 * or under a proprietary license.
 *
 * The texts of the GNU Affero General Public License with an additional
 * permission and of our proprietary license can be found at and
 * in the LICENSE file you have received along with this program.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * "Shopware" is a registered trademark of shopware AG.
 * The licensing of the program under the AGPLv3 does not imply a
 * trademark license. Therefore any rights, title and interest in
 * our trademarks remain entirely with us.
 */

/**
 * Shopware ImportExport Plugin
 *
 * @category Shopware
 * @package Shopware\Plugins\SwagImageEditor
 * @copyright Copyright (c) shopware AG (http://www.shopware.de)
 */
class Shopware_Controllers_Backend_SwagImportExport extends Shopware_Controllers_Backend_ExtJs
{
    /**
     * @var Shopware\Models\Category\Category
     */
    protected $profileRepository;

    protected function convertToExtJSTree($node, $isInIteration = false)
    {
        $extjsNode = array();

        if ($node['type'] == 'record') {
            $isIteration = true;

            $extjsNode['iconCls'] = 'sprite-blue-folders-stack';
        } else {
            $isIteration = false;

            if ($isInIteration) {
                $extjsNode['iconCls'] = 'sprite-icon_taskbar_top_inhalte_active';
            }
        }

        if (isset($node['name'])) {
            $extjsNode['text'] = $node['name'];
        }
        if (isset($node['children'])) {
            $extjsNode['expanded'] = true;
            foreach ($node['children'] as $child) {
                $extjsNode['children'][] = $this->convertToExtJSTree($child, $isIteration | $isInIteration);
            }
        }
        if (isset($node['attributes'])) {
            if (!isset($extjsNode['children'])) {
                $extjsNode['expanded'] = true;
                $extjsNode['children'] = array();
            }
            foreach ($node['attributes'] as $attribute) {
                $extjsNode['children'][] = array('text' => $attribute['name'], 'leaf' => true, 'iconCls' => 'sprite-sticky-notes-pin');
            }
        }
        if (!isset($extjsNode['children'])) {
            if ($isInIteration) {
                $extjsNode['leaf'] = true;
            } else {
                $extjsNode['expanded'] = true;
                $extjsNode['children'] = array();
            }
        }

        return $extjsNode;
    }

    public function getProfileAction()
    {
        $postData = array(
            'profileId' => 1,
            'sessionId' => 70,
            'type' => 'export',
            'limit' => array('limit' => 40, 'offset' => 0),
            'max_record_count' => 100,
            'format' => 'xml',
            'adapter' => 'categories',
        );

        $profile = $this->Plugin()->getProfileFactory()->loadProfile($postData);
        $root = $this->convertToExtJSTree(json_decode($profile->getConfig('tree'), 1));

        $this->View()->assign(array('success' => true, 'children' => $root['children']));
    }
    
    /**
     * Returns all profiles into an array
     */
    public function getProfilesAction()
    {
        $profileRepository = $this->getProfileRepository();
        
        $query = $profileRepository->getProfilesListQuery(
            $this->Request()->getParam('filter', array()),
            $this->Request()->getParam('sort', array()),
            $this->Request()->getParam('limit', null),
            $this->Request()->getParam('start')
        )->getQuery();
        
        $count = Shopware()->Models()->getQueryCount($query);

        $data = $query->getArrayResult();
        
        $this->View()->assign(array(
            'success' => true, 'data' => $data, 'total' => $count
        ));
    }
    
    /**
     * Helper Method to get access to the category repository.
     *
     * @return Shopware\Models\Category\Repository
     */
    public function getProfileRepository()
    {
        if ($this->profileRepository === null) {
            $this->profileRepository = Shopware()->Models()->getRepository('Shopware\CustomModels\ImportExport\Profile');
        }
        return $this->profileRepository;
    }

    public function Plugin()
    {
        return Shopware()->Plugins()->Backend()->SwagImportExport();
    }

}
