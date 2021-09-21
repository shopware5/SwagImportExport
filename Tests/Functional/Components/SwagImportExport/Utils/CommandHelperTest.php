<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Functional\Components\SwagImportExport\Utils;

use PHPUnit\Framework\TestCase;
use Shopware\Components\SwagImportExport\Utils\CommandHelper;
use Shopware\Tests\Functional\Traits\DatabaseTransactionBehaviour;

class CommandHelperTest extends TestCase
{
    use DatabaseTransactionBehaviour;

    public function testGetProductStreamIdByNameShouldBeNull()
    {
        $commandHelper = new CommandHelper([
            'profileEntity' => 'unitTest',
            'format' => 'unitTest',
            'filePath' => 'unitTest',
        ]);

        static::assertNull($this->getProductStreamValue($commandHelper));
    }

    public function testGetProductStreamIdByNameShouldBeLikeGivenId()
    {
        $commandHelper = new CommandHelper([
            'profileEntity' => 'unitTest',
            'format' => 'unitTest',
            'filePath' => 'unitTest',
            'productStream' => '12',
        ]);

        static::assertSame('12', $this->getProductStreamValue($commandHelper));
    }

    public function testGetProductStreamIdByNameShouldFoundCorrectId()
    {
        $sql = \file_get_contents(__DIR__ . '/_fixtures/stream.sql');
        Shopware()->Container()->get('dbal_connection')->exec($sql);

        $commandHelper = new CommandHelper([
            'profileEntity' => 'unitTest',
            'format' => 'unitTest',
            'filePath' => 'unitTest',
            'productStream' => 'TestStream',
        ]);

        static::assertSame('1', $this->getProductStreamValue($commandHelper));
    }

    public function testGetProductStreamIdByNameExpectExceptionNoStreamFound()
    {
        static::expectException(\RuntimeException::class);
        static::expectExceptionMessage('There are no streams with the name: TestStream');

        new CommandHelper([
            'profileEntity' => 'unitTest',
            'format' => 'unitTest',
            'filePath' => 'unitTest',
            'productStream' => 'TestStream',
        ]);
    }

    public function testGetProductStreamIdByNameExpectExceptionMultipleStreamsFound()
    {
        $sql = \file_get_contents(__DIR__ . '/_fixtures/multiple_streams.sql');
        Shopware()->Container()->get('dbal_connection')->exec($sql);

        static::expectException(\RuntimeException::class);
        static::expectExceptionMessage('There are 2 streams with the name: TestStream. Please use the stream id.');

        new CommandHelper([
            'profileEntity' => 'unitTest',
            'format' => 'unitTest',
            'filePath' => 'unitTest',
            'productStream' => 'TestStream',
        ]);
    }

    private function getProductStreamValue(CommandHelper $commandHelper)
    {
        $reflectionProperty = (new \ReflectionClass(CommandHelper::class))->getProperty('productStream');
        $reflectionProperty->setAccessible(true);

        return $reflectionProperty->getValue($commandHelper);
    }
}
