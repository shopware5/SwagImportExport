<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport\Components\Service\Struct;

use SwagImportExport\CustomModels\Profile;

class ProfileDataStruct
{
    private string $name;

    private string $type;

    private $tree;

    public function __construct(Profile $profile)
    {
        $this->name = $profile->getName();
        $this->type = $profile->getType();
        $this->tree = \json_decode($profile->getTree(), true);
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    public function getTree()
    {
        return $this->tree;
    }

    /**
     * @return string
     */
    public function getExportData()
    {
        return \json_encode($this->asArray(), \JSON_PRETTY_PRINT);
    }

    /**
     * @return array
     */
    private function asArray()
    {
        return [
            'name' => $this->getName(),
            'type' => $this->getType(),
            'tree' => $this->getTree(),
        ];
    }
}
