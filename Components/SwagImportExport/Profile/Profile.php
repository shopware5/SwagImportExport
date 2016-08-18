<?php

/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Shopware\Components\SwagImportExport\Profile;

/**
 * Class Profile
 *
 * @package Shopware\Components\SwagImportExport\Profile
 */
class Profile
{
    /** @var  $profileEntity \Shopware\CustomModels\ImportExport\Profile */
    private $profileEntity;
    
    /** @var array $configNames */
    private $configNames;

    /**
     * @var array
     */
    private $defaultValues = array();

    public function __construct($profile, array $configNames = array())
    {
        $this->profileEntity = $profile;
        $this->configNames = $configNames ?: array('exportConversion', 'tree', 'decimals');
    }

    public function getId()
    {
        return $this->profileEntity->getId();
    }

    public function getType()
    {
        return $this->profileEntity->getType();
    }
    
    public function getName()
    {
        return $this->profileEntity->getName();
    }

    public function getConfigNames()
    {
        return $this->configNames;
    }

    public function getConfig($name)
    {
        switch ($name) {
            case 'exportConversion':
                return $this->profileEntity->getExpressions();
            case 'tree':
                return $this->profileEntity->getTree();
            case 'decimals':
                return [Shopware()->Plugins()->Backend()->SwagImportExport()->Config(), $this];
            default:
                throw new \Exception('Config does not exists');
        }
    }

    public function setConfig($name, $value)
    {
        switch ($name) {
            case 'tree':
                $this->profileEntity->setTree($value);
                break;
            default:
                throw new \Exception('Config does not exists');
        }
    }
    
    public function getEntity()
    {
        return $this->profileEntity;
    }
    
    public function persist()
    {
        Shopware()->Models()->persist($this->profileEntity);
    }

    /**
     * Check if current node have default value
     *
     * @param array $node
     * @return array
     */
    private function getDefaultFields($node)
    {
        if ($node) {
            foreach ($node['children'] as $key => $leaf) {
                if ($leaf['children']) {
                    $this->getDefaultFields($leaf);
                }

                if (isset($leaf['defaultValue']) && $leaf['defaultValue'] != '') {
                    $this->defaultValues[$leaf['shopwareField']] = $leaf['defaultValue'];
                }
            }
        }

        return $this->defaultValues;
    }

    /**
     * Return list with default fields and values for current profile
     *
     * @param array $tree profile tree
     * @return array
     */
    public function getDefaultValues($tree)
    {
        return $this->getDefaultFields($tree);
    }
}
