<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport\Tests\Unit\Setup;

use Shopware\Setup\SwagImportExport\SetupContext;

class SetupContextTest extends \PHPUnit_Framework_TestCase
{
    const DEV_VERSION = '___VERSION___';
    const CURRENT_SHOPWARE_VERSION = '5.3.0';

    public function test_assertShopwareVersionLowerThan_should_return_true_if_dev_version_is_used()
    {
        $setupContext = new SetupContext(self::DEV_VERSION, '', '');
        $isCompatible = $setupContext->assertShopwareVersionLowerThan('');

        $this->assertTrue($isCompatible);
    }

    public function test_assertShopwareVersionLowerThan_should_return_false()
    {
        $currentShopwareVersion = '5.3.0';
        $minVersion = '5.4.0';

        $setupContext = new SetupContext($currentShopwareVersion, '', '');
        $isCompatible = $setupContext->assertShopwareVersionLowerThan($minVersion);

        $this->assertFalse($isCompatible);
    }

    public function test_assertShopwareVersionLowerThan_should_return_true()
    {
        $currentShopwareVersion = '5.3.0';
        $minVersion = '5.2.0';

        $setupContext = new SetupContext($currentShopwareVersion, '', '');
        $isCompatible = $setupContext->assertShopwareVersionLowerThan($minVersion);

        $this->assertTrue($isCompatible);
    }

    public function test_assertShopwareVersionLowerThan_should_return_true_if_same_versions_were_given()
    {
        $currentShopwareVersion = '5.3.0';
        $minVersion = '5.3.0';

        $setupContext = new SetupContext($currentShopwareVersion, '', '');
        $isCompatible = $setupContext->assertShopwareVersionLowerThan($minVersion);

        $this->assertTrue($isCompatible);
    }

    public function test_assertMaximumPluginVersion_should_return_false()
    {
        $currentPluginVersion = '1.0.0';
        $maxVersion = '0.9.0';

        $setupContext = new SetupContext('', $currentPluginVersion, '');
        $isCompatible = $setupContext->assertMaximumPluginVersion($maxVersion);

        $this->assertFalse($isCompatible);
    }

    public function test_assertMaximumPluginVersion_should_return_true()
    {
        $currentPluginVersion = '1.0.0';
        $maxVersion = '1.1.0';

        $setupContext = new SetupContext('', $currentPluginVersion, '');
        $isCompatible = $setupContext->assertMaximumPluginVersion($maxVersion);

        $this->assertTrue($isCompatible);
    }

    public function test_assertMaximumPluginVersion_should_return_false_if_same_versions_were_given()
    {
        $currentPluginVersion = '1.1.0';
        $maxVersion = '1.1.0';

        $setupContext = new SetupContext('', $currentPluginVersion, '');
        $isCompatible = $setupContext->assertMaximumPluginVersion($maxVersion);

        $this->assertFalse($isCompatible);
    }

    public function test_assertPluginVersionLowerThan_should_return_false()
    {
        $currentPluginVersion = '1.0.0';
        $minVersion = '1.1.0';

        $setupContext = new SetupContext('', $currentPluginVersion, '');
        $isCompatible = $setupContext->assertPluginVersionLowerThan($minVersion);

        $this->assertFalse($isCompatible);
    }

    public function test_assertPluginVersionLowerThan_should_return_true()
    {
        $currentPluginVersion = '1.0.0';
        $minVersion = '0.9.0';

        $setupContext = new SetupContext('', $currentPluginVersion, '');
        $isCompatible = $setupContext->assertPluginVersionLowerThan($minVersion);

        $this->assertTrue($isCompatible);
    }

    public function test_assertPluginVersionLowerThan_should_return_true_if_same_versions_were_given()
    {
        $currentPluginVersion = '1.0.0';
        $minVersion = '1.0.0';

        $setupContext = new SetupContext('', $currentPluginVersion, '');
        $isCompatible = $setupContext->assertPluginVersionLowerThan($minVersion);

        $this->assertTrue($isCompatible);
    }

    public function test_assertMaximumShopwareVersion_should_return_true()
    {
        $maxVersion = '5.2.0';
        $currentShopwareVersion = '5.1.0';

        $setupContext = new SetupContext($currentShopwareVersion, '', '');
        $isCompatible = $setupContext->assertMaximumShopwareVersion($maxVersion);

        $this->assertTrue($isCompatible);
    }

    public function test_assertMaximumShopwareVersion_should_return_false()
    {
        $maxVersion = '5.2.0';
        $currentShopwareVersion = '5.3.0';

        $setupContext = new SetupContext($currentShopwareVersion, '', '');
        $isCompatible = $setupContext->assertMaximumShopwareVersion($maxVersion);

        $this->assertFalse($isCompatible);
    }

    public function test_assertMaximumShopwareVersion_with_dev_version_should_return_false()
    {
        $requiredShopwareVersion = '5.2.0';

        $setupContext = new SetupContext(self::DEV_VERSION, '', '');
        $isCompatible = $setupContext->assertMaximumShopwareVersion($requiredShopwareVersion);

        $this->assertFalse($isCompatible);
    }

    public function test_assertMaximumShopwareVersion_should_return_false_if_same_versions_were_given()
    {
        $currentShopwareVersion = '5.3.0';
        $maxVersion = '5.3.0';

        $setupContext = new SetupContext($currentShopwareVersion, '', '');
        $isCompatible = $setupContext->assertMaximumShopwareVersion($maxVersion);

        $this->assertFalse($isCompatible);
    }
}
