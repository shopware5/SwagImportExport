<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Shopware\Components\SwagImportExport\Profile;

use Shopware\Components\Plugin\Configuration\CachedReader;
use Shopware\CustomModels\ImportExport\Profile as ProfileEntity;

class Profile
{
    /** @var ProfileEntity */
    private $profileEntity;

    /** @var array */
    private $configNames;

    /**
     * @var array
     */
    private $defaultValues = [];

    /**
     * @param ProfileEntity $profile
     */
    public function __construct($profile, array $configNames = [])
    {
        $this->profileEntity = $profile;
        $this->configNames = $configNames ?: ['exportConversion', 'tree', 'decimals'];
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->profileEntity->getId();
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->profileEntity->getType();
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->profileEntity->getName();
    }

    /**
     * @return array
     */
    public function getConfigNames()
    {
        return $this->configNames;
    }

    /**
     * @throws \RuntimeException
     *
     * @return array|\Shopware\CustomModels\ImportExport\Expression[]|string|array{0: \Enlight_Config, 1: Profile}
     */
    public function getConfig($name)
    {
        switch ($name) {
            case 'exportConversion':
                return $this->profileEntity->getExpressions();
            case 'tree':
                return $this->profileEntity->getTree();
            case 'decimals':
                return [new \Enlight_Config(Shopware()->Container()->get(CachedReader::class)->getByPluginName($this->getName()), true), $this];
            default:
                throw new \RuntimeException('Config does not exists');
        }
    }

    /**
     * @throws \RuntimeException
     */
    public function setConfig($name, $value)
    {
        switch ($name) {
            case 'tree':
                $this->profileEntity->setTree($value);
                break;
            default:
                throw new \RuntimeException('Config does not exists');
        }
    }

    /**
     * @return ProfileEntity
     */
    public function getEntity()
    {
        return $this->profileEntity;
    }

    public function persist()
    {
        Shopware()->Models()->persist($this->profileEntity);
    }

    /**
     * Return list with default fields and values for current profile
     *
     * @param array $tree profile tree
     *
     * @return array
     */
    public function getDefaultValues($tree)
    {
        return $this->getDefaultFields($tree);
    }

    /**
     * Check if current node have default value
     *
     * @param array $node
     *
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
}
