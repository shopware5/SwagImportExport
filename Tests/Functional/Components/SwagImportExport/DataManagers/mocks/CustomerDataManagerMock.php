<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport\Tests\Functional\Components\SwagImportExport\DataManagers\mocks;

use Shopware\Components\SwagImportExport\DataManagers\CustomerDataManager;

class CustomerDataManagerMock extends CustomerDataManager
{
    public function getDefaultFields()
    {
        return [
            'string' => [
                'encoder',
                'customernnumber',
            ],
        ];
    }
}
