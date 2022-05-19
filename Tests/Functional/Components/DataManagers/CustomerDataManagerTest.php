<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport\Tests\Functional\Components\DataManagers;

use PHPUnit\Framework\TestCase;
use SwagImportExport\Tests\Functional\Components\DataManagers\Mocks\CustomerDataManagerMock;
use SwagImportExport\Tests\Helper\ContainerTrait;

class CustomerDataManagerTest extends TestCase
{
    use ContainerTrait;

    public function testSetDefaultFieldsForCreateShouldAddANewCustomerNumber()
    {
        $record = [];
        $defaultFields = [];

        $service = new CustomerDataManagerMock(
            $this->getContainer()->get('db'),
            $this->getContainer()->get('config'),
            $this->getContainer()->get('passwordencoder'),
            $this->getContainer()->get('shopware.number_range_incrementer')
        );

        $result = $service->setDefaultFieldsForCreate($record, $defaultFields);

        static::assertSame('bcrypt', $result['encoder']);
        static::assertStringStartsWith('200', $result['customernnumber']);
    }
}
