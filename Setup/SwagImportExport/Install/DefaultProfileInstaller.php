<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Shopware\Setup\SwagImportExport\Install;

use Doctrine\DBAL\Connection;
use Shopware\Setup\SwagImportExport\DefaultProfiles\ArticleCompleteProfile;
use Shopware\Setup\SwagImportExport\DefaultProfiles\CategoryProfile;
use Shopware\Setup\SwagImportExport\DefaultProfiles\ArticleProfile;
use Shopware\Setup\SwagImportExport\DefaultProfiles\MinimalArticleProfile;
use Shopware\Setup\SwagImportExport\DefaultProfiles\MinimalArticleVariantsProfile;
use Shopware\Setup\SwagImportExport\DefaultProfiles\MinimalCategoryProfile;
use Shopware\Setup\SwagImportExport\DefaultProfiles\NewsletterRecipientProfile;
use Shopware\Setup\SwagImportExport\DefaultProfiles\ArticlePriceProfile;
use Shopware\Setup\SwagImportExport\DefaultProfiles\ArticleImageUrlProfile;
use Shopware\Setup\SwagImportExport\DefaultProfiles\ArticleTranslationUpdateProfile;
use Shopware\Setup\SwagImportExport\DefaultProfiles\ProfileMetaData;
use Shopware\Setup\SwagImportExport\SetupContext;

class DefaultProfileInstaller implements InstallerInterface
{
    const MIN_PLUGIN_VERSION = '2.0.0';

    /**
     * @var Connection
     */
    private $connection;

    /**
     * @var SetupContext
     */
    private $setupContext;

    /**
     * @param SetupContext $setupContext
     * @param Connection $connection
     */
    public function __construct(SetupContext $setupContext, Connection $connection)
    {
        $this->connection = $connection;
        $this->setupContext = $setupContext;
    }

    /**
     * @inheritdoc
     */
    public function install()
    {
        foreach ($this->getProfiles() as $profile) {
            $serializedTree = json_encode($profile);

            $sql = '
INSERT IGNORE INTO s_import_export_profile 
(`type`, `name`, `tree`, `hidden`, `is_default`) 
VALUES 
(:type, :name, :tree, :hidden, :is_default)';

            $params = [
                'type' => $profile->getAdapter(),
                'name' => $profile->getName(),
                'tree' => $serializedTree,
                'hidden' => 0,
                'is_default' => 1
            ];

            $this->connection->executeQuery($sql, $params);
        }
    }

    /**
     * @return boolean
     */
    public function isCompatible()
    {
        return $this->setupContext->assertMinimumPluginVersion(self::MIN_PLUGIN_VERSION);
    }

    /**
     * @return ProfileMetaData[]
     */
    protected function getProfiles()
    {
        return [
            new MinimalCategoryProfile(),
            new CategoryProfile(),
            new ArticleCompleteProfile(),
            new ArticleProfile(),
            new NewsletterRecipientProfile(),
            new MinimalArticleProfile(),
            new ArticlePriceProfile(),
            new ArticleImageUrlProfile(),
            new MinimalArticleVariantsProfile(),
            new ArticleTranslationUpdateProfile()
        ];
    }
}
