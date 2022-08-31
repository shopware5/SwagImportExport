<?php
declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport\Tests\Unit\Setup\DefaultProfiles;

use PHPUnit\Framework\TestCase;
use SwagImportExport\Setup\DefaultProfiles\MinimalProductProfile;

class MinimalProductProfileTest extends TestCase
{
    use DefaultProfileTestCaseTrait;

    public function testItShouldReturnValidProfileTree(): void
    {
        $minimalProductProfile = $this->getMinimalProductProfile();

        $profileTree = $minimalProductProfile->jsonSerialize();
        $this->walkRecursive($profileTree, function ($node): void {
            static::assertArrayHasKey('id', $node, 'Current array: ' . \print_r($node, true));
            static::assertArrayHasKey('type', $node, 'Current array: ' . \print_r($node, true));
            static::assertArrayHasKey('name', $node, 'Current array: ' . \print_r($node, true));
        });

        $profileJson = \json_encode($minimalProductProfile, \JSON_THROW_ON_ERROR);
        static::assertJson($profileJson);
    }

    private function getMinimalProductProfile(): MinimalProductProfile
    {
        return new MinimalProductProfile();
    }
}
