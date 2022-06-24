<?php
declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport\Components\Service\Struct;

use SwagImportExport\Models\Profile;

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

    public function getName(): string
    {
        return $this->name;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getTree(): array
    {
        return $this->tree;
    }

    public function getExportData(): string
    {
        return \json_encode($this->asArray(), \JSON_PRETTY_PRINT);
    }

    /**
     * @return array{name: string, type: string, tree: array}
     */
    private function asArray(): array
    {
        return [
            'name' => $this->getName(),
            'type' => $this->getType(),
            'tree' => $this->getTree(),
        ];
    }
}
