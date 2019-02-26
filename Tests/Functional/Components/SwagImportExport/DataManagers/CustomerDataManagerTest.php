<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport\Tests\Functional\Components\SwagImportExport\DataManagers;

use PHPUnit\Framework\TestCase;
use SwagImportExport\Tests\Functional\Components\SwagImportExport\DataManagers\mocks\CustomerDataManagerMock;

class CustomerDataManagerTest extends TestCase
{
    public function test_setDefaultFieldsForCreate_shouldAddANewCustomerNumber()
    {
        $record = [];
        $defaultFields = [];

        $service = new CustomerDataManagerMock();
        $result = $service->setDefaultFieldsForCreate($record, $defaultFields);

        static::assertSame('bcrypt', $result['encoder']);
        static::assertStringStartsWith('200', $result['customernnumber']);
    }
}
