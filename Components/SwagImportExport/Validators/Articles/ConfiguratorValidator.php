<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Shopware\Components\SwagImportExport\Validators\Articles;

use Shopware\Components\SwagImportExport\Validators\Validator;

class ConfiguratorValidator extends Validator
{
    public static $mapper = [
        'int' => [
            'configSetId',
            'configSetType',
        ],
        'string' => [ // TODO: maybe we don't need to check fields which contains string?
            'configGroupName',
            'configOptionName',
        ],
    ];

    public function checkRequiredFields($record)
    {
    }
}
