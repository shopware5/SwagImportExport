<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport\Tests\Unit\Setup\DefaultProfiles;

use PHPUnit\Framework\TestCase;
use SwagImportExport\Setup\DefaultProfiles\MinimalArticleProfile;
use SwagImportExport\Setup\DefaultProfiles\ProfileMetaData;

class MinimalArticleProfileTest extends TestCase
{
    use DefaultProfileTestCaseTrait;

    public function testItCanBeCreated()
    {
        $minimalArticleProfile = $this->getMinimalArticleProfile();

        static::assertInstanceOf(MinimalArticleProfile::class, $minimalArticleProfile);
        static::assertInstanceOf(\JsonSerializable::class, $minimalArticleProfile);
        static::assertInstanceOf(ProfileMetaData::class, $minimalArticleProfile);
    }

    public function testItShouldReturnValidProfileTree()
    {
        $minimalArticleProfile = $this->getMinimalArticleProfile();

        $profileTree = $minimalArticleProfile->jsonSerialize();
        $this->walkRecursive($profileTree, function ($node) {
            $this->assertArrayHasKey('id', $node, 'Current array: ' . \print_r($node, true));
            $this->assertArrayHasKey('type', $node, 'Current array: ' . \print_r($node, true));
            $this->assertArrayHasKey('name', $node, 'Current array: ' . \print_r($node, true));
        });

        $profileJson = \json_encode($minimalArticleProfile);
        static::assertJson($profileJson);
    }

    /**
     * @return MinimalArticleProfile
     */
    private function getMinimalArticleProfile()
    {
        return new MinimalArticleProfile();
    }
}
