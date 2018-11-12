<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Shopware\Setup\SwagImportExport\Update;

use Doctrine\DBAL\Connection;
use Shopware\Setup\SwagImportExport\DefaultProfiles\CategoryTranslationProfile;
use Shopware\Setup\SwagImportExport\SetupContext;

class Update06CreateCategoryTranslationProfile implements UpdaterInterface
{
    const MAX_PLUGIN_VERSION = '2.7.0';

    /**
     * @var SetupContext
     */
    private $setupContext;

    /**
     * @var Connection
     */
    private $connection;

    /**
     * @param SetupContext $setupContext
     * @param Connection   $connection
     */
    public function __construct(SetupContext $setupContext, Connection $connection)
    {
        $this->setupContext = $setupContext;
        $this->connection = $connection;
    }

    /**
     * {@inheritdoc}
     */
    public function update()
    {
        $sql = '
            INSERT IGNORE INTO s_import_export_profile
            (`type`, `name`, `description`, `tree`, `hidden`, `is_default`)
            VALUES
            (:type, :name, :description, :tree, :hidden, :is_default)
        ';

        $profile = new CategoryTranslationProfile();
        $serializedTree = json_encode($profile);

        $params = [
            'type' => $profile->getAdapter(),
            'name' => $profile->getName(),
            'description' => $profile->getDescription(),
            'tree' => $serializedTree,
            'hidden' => 0,
            'is_default' => 1,
        ];

        $this->connection->executeQuery($sql, $params);
    }

    /**
     * {@inheritdoc}
     */
    public function isCompatible()
    {
        return version_compare($this->setupContext->getPreviousPluginVersion(), self::MAX_PLUGIN_VERSION, '<');
    }
}
