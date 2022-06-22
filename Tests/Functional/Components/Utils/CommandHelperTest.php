<?php
declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport\Tests\Functional\Components\Utils;

use PHPUnit\Framework\TestCase;
use Shopware\Tests\Functional\Traits\DatabaseTransactionBehaviour;
use SwagImportExport\Components\Utils\CommandHelper;
use SwagImportExport\CustomModels\Profile;
use SwagImportExport\Tests\Helper\ContainerTrait;

class CommandHelperTest extends TestCase
{
    use DatabaseTransactionBehaviour;
    use ContainerTrait;

    public function testGetProductStreamIdByNameShouldBeNull(): void
    {
        $commandHelper = new CommandHelper([
            'profileEntity' => new Profile(),
            'format' => 'unitTest',
            'filePath' => 'unitTest',
        ]);

        static::assertNull($this->getProductStreamValue($commandHelper));
    }

    public function testGetProductStreamIdByNameShouldBeLikeGivenId(): void
    {
        $commandHelper = new CommandHelper([
            'profileEntity' => new Profile(),
            'format' => 'unitTest',
            'filePath' => 'unitTest',
            'productStream' => '12',
        ]);

        static::assertSame(12, $this->getProductStreamValue($commandHelper));
    }

    public function testGetProductStreamIdByNameShouldFoundCorrectId(): void
    {
        $sql = \file_get_contents(__DIR__ . '/_fixtures/stream.sql');
        static::assertIsString($sql);
        $this->getContainer()->get('dbal_connection')->executeStatement($sql);

        $commandHelper = new CommandHelper([
            'profileEntity' => new Profile(),
            'format' => 'unitTest',
            'filePath' => 'unitTest',
            'productStream' => 'TestStream',
        ]);

        static::assertSame(1, $this->getProductStreamValue($commandHelper));
    }

    public function testGetProductStreamIdByNameExpectExceptionNoStreamFound(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('There are no streams with the name: TestStream');

        new CommandHelper([
            'profileEntity' => new Profile(),
            'format' => 'unitTest',
            'filePath' => 'unitTest',
            'productStream' => 'TestStream',
        ]);
    }

    public function testGetProductStreamIdByNameExpectExceptionMultipleStreamsFound(): void
    {
        $sql = \file_get_contents(__DIR__ . '/_fixtures/multiple_streams.sql');
        static::assertIsString($sql);
        $this->getContainer()->get('dbal_connection')->executeStatement($sql);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('There are 2 streams with the name: TestStream. Please use the stream id.');

        new CommandHelper([
            'profileEntity' => new Profile(),
            'format' => 'unitTest',
            'filePath' => 'unitTest',
            'productStream' => 'TestStream',
        ]);
    }

    private function getProductStreamValue(CommandHelper $commandHelper): ?int
    {
        $reflectionProperty = (new \ReflectionClass(CommandHelper::class))->getProperty('productStream');
        $reflectionProperty->setAccessible(true);

        return $reflectionProperty->getValue($commandHelper);
    }
}
