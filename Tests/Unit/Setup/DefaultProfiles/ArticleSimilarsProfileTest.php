<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport\Tests\Unit\Setup\DefaultProfiles;

use PHPUnit\Framework\TestCase;
use Shopware\Setup\SwagImportExport\DefaultProfiles\ArticleSimilarsProfile;
use Shopware\Setup\SwagImportExport\DefaultProfiles\ProfileMetaData;

class ArticleSimilarsProfileTest extends TestCase
{
    use DefaultProfileTestCaseTrait;

    public function test_it_can_be_created()
    {
        $articleSimilarsProfile = $this->createArticlSimilarsProfile();

        static::assertInstanceOf(ArticleSimilarsProfile::class, $articleSimilarsProfile);
        static::assertInstanceOf(ProfileMetaData::class, $articleSimilarsProfile);
        static::assertInstanceOf(\JsonSerializable::class, $articleSimilarsProfile);
    }

    public function test_it_should_return_valid_profile_tree()
    {
        $articleSimilarsProfile = $this->createArticlSimilarsProfile();

        $this->walkRecursive($articleSimilarsProfile->jsonSerialize(), function ($node) {
            $this->assertArrayHasKey('id', $node, 'Current array: ' . print_r($node, true));
            $this->assertArrayHasKey('name', $node, 'Current array: ' . print_r($node, true));
            $this->assertArrayHasKey('type', $node, 'Current array: ' . print_r($node, true));
        });
    }

    /**
     * @return ArticleSimilarsProfile
     */
    private function createArticlSimilarsProfile()
    {
        return new ArticleSimilarsProfile();
    }
}
