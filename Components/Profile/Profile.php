<?php
declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport\Components\Profile;

use Shopware\Components\Plugin\Configuration\CachedReader;
use SwagImportExport\CustomModels\Profile as ProfileEntity;

class Profile
{
    private ProfileEntity $profileEntity;

    private array $configNames;

    private array $defaultValues = [];

    public function __construct(ProfileEntity $profile, array $configNames = [])
    {
        $this->profileEntity = $profile;
        $this->configNames = $configNames ?: ['exportConversion', 'tree', 'decimals'];
    }

    public function getId(): int
    {
        return $this->profileEntity->getId();
    }

    public function getType(): string
    {
        return $this->profileEntity->getType();
    }

    public function getName(): string
    {
        return $this->profileEntity->getName();
    }

    public function getConfigNames(): array
    {
        return $this->configNames;
    }

    /**
     * @return array|\SwagImportExport\CustomModels\Expression[]|string|array{0: \Enlight_Config, 1: Profile}
     */
    public function getConfig(string $name)
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

    public function setConfig(string $name, string $value): void
    {
        switch ($name) {
            case 'tree':
                $this->profileEntity->setTree($value);
                break;
            default:
                throw new \RuntimeException('Config does not exists');
        }
    }

    public function getEntity(): ProfileEntity
    {
        return $this->profileEntity;
    }

    public function persist(): void
    {
        Shopware()->Models()->persist($this->profileEntity);
    }

    /**
     * Return list with default fields and values for current profile
     *
     * @param array $tree profile tree
     */
    public function getDefaultValues(array $tree): array
    {
        return $this->getDefaultFields($tree);
    }

    /**
     * Check if current node have default value
     */
    private function getDefaultFields(array $node): array
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
