<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport\Tests\Unit\Setup;

use PHPUnit\Framework\TestCase;
use Shopware\Setup\SwagImportExport\SetupContext;

class SetupContextTest extends TestCase
{
    public const DEV_VERSION = '___VERSION___';
    public const CURRENT_SHOPWARE_VERSION = '5.3.0';

    public function testAssertMinimumShopwareVersionShouldReturnTrueIfDevVersionIsUsed()
    {
        $setupContext = new SetupContext(self::DEV_VERSION, '', '');
        $isCompatible = $setupContext->assertMinimumShopwareVersion('');

        static::assertTrue($isCompatible);
    }

    public function testAssertMinimumShopwareVersionShouldReturnFalse()
    {
        $currentShopwareVersion = '5.3.0';
        $minVersion = '5.4.0';

        $setupContext = new SetupContext($currentShopwareVersion, '', '');
        $isCompatible = $setupContext->assertMinimumShopwareVersion($minVersion);

        static::assertFalse($isCompatible);
    }

    public function testAssertMinimumShopwareVersionShouldReturnTrue()
    {
        $currentShopwareVersion = '5.3.0';
        $minVersion = '5.2.0';

        $setupContext = new SetupContext($currentShopwareVersion, '', '');
        $isCompatible = $setupContext->assertMinimumShopwareVersion($minVersion);

        static::assertTrue($isCompatible);
    }

    public function testAssertMinimumShopwareVersionShouldReturnTrueIfSameVersionsWereGiven()
    {
        $currentShopwareVersion = '5.3.0';
        $minVersion = '5.3.0';

        $setupContext = new SetupContext($currentShopwareVersion, '', '');
        $isCompatible = $setupContext->assertMinimumShopwareVersion($minVersion);

        static::assertTrue($isCompatible);
    }

    public function testAssertMaximumPluginVersionShouldReturnFalse()
    {
        $currentPluginVersion = '1.0.0';
        $maxVersion = '0.9.0';

        $setupContext = new SetupContext('', $currentPluginVersion, '');
        $isCompatible = $setupContext->assertMaximumPluginVersion($maxVersion);

        static::assertFalse($isCompatible);
    }

    public function testAssertMaximumPluginVersionShouldReturnTrue()
    {
        $currentPluginVersion = '1.0.0';
        $maxVersion = '1.1.0';

        $setupContext = new SetupContext('', $currentPluginVersion, '');
        $isCompatible = $setupContext->assertMaximumPluginVersion($maxVersion);

        static::assertTrue($isCompatible);
    }

    public function testAssertMaximumPluginVersionShouldReturnFalseIfSameVersionsWereGiven()
    {
        $currentPluginVersion = '1.1.0';
        $maxVersion = '1.1.0';

        $setupContext = new SetupContext('', $currentPluginVersion, '');
        $isCompatible = $setupContext->assertMaximumPluginVersion($maxVersion);

        static::assertFalse($isCompatible);
    }

    public function testAssertMinimumPluginVersionShouldReturnFalse()
    {
        $currentPluginVersion = '1.0.0';
        $minVersion = '1.1.0';

        $setupContext = new SetupContext('', $currentPluginVersion, '');
        $isCompatible = $setupContext->assertMinimumPluginVersion($minVersion);

        static::assertFalse($isCompatible);
    }

    public function testAssertMinimumPluginVersionShouldReturnTrue()
    {
        $currentPluginVersion = '1.0.0';
        $minVersion = '0.9.0';

        $setupContext = new SetupContext('', $currentPluginVersion, '');
        $isCompatible = $setupContext->assertMinimumPluginVersion($minVersion);

        static::assertTrue($isCompatible);
    }

    public function testAssertMinimumPluginVersionShouldReturnTrueIfSameVersionsWereGiven()
    {
        $currentPluginVersion = '1.0.0';
        $minVersion = '1.0.0';

        $setupContext = new SetupContext('', $currentPluginVersion, '');
        $isCompatible = $setupContext->assertMinimumPluginVersion($minVersion);

        static::assertTrue($isCompatible);
    }

    public function testAssertMaximumShopwareVersionShouldReturnTrue()
    {
        $maxVersion = '5.2.0';
        $currentShopwareVersion = '5.1.0';

        $setupContext = new SetupContext($currentShopwareVersion, '', '');
        $isCompatible = $setupContext->assertMaximumShopwareVersion($maxVersion);

        static::assertTrue($isCompatible);
    }

    public function testAssertMaximumShopwareVersionShouldReturnFalse()
    {
        $maxVersion = '5.2.0';
        $currentShopwareVersion = '5.3.0';

        $setupContext = new SetupContext($currentShopwareVersion, '', '');
        $isCompatible = $setupContext->assertMaximumShopwareVersion($maxVersion);

        static::assertFalse($isCompatible);
    }

    public function testAssertMaximumShopwareVersionWithDevVersionShouldReturnFalse()
    {
        $requiredShopwareVersion = '5.2.0';

        $setupContext = new SetupContext(self::DEV_VERSION, '', '');
        $isCompatible = $setupContext->assertMaximumShopwareVersion($requiredShopwareVersion);

        static::assertFalse($isCompatible);
    }

    public function testAssertMaximumShopwareVersionShouldReturnFalseIfSameVersionsWereGiven()
    {
        $currentShopwareVersion = '5.3.0';
        $maxVersion = '5.3.0';

        $setupContext = new SetupContext($currentShopwareVersion, '', '');
        $isCompatible = $setupContext->assertMaximumShopwareVersion($maxVersion);

        static::assertFalse($isCompatible);
    }
}
