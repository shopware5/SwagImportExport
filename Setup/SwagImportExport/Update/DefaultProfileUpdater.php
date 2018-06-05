<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Shopware\Setup\SwagImportExport\Update;

use Doctrine\DBAL\Connection;
use Shopware\Setup\SwagImportExport\DefaultProfiles\ProfileHelper;
use Shopware\Setup\SwagImportExport\DefaultProfiles\ProfileMetaData;
use Shopware\Setup\SwagImportExport\SetupContext;

class DefaultProfileUpdater implements UpdaterInterface
{
    const MIN_PLUGIN_VERSION = '2.0.0';

    /**
     * @var Connection
     */
    private $connection;

    /**
     * @var \Shopware_Components_Snippet_Manager
     */
    private $snippetManager;

    /**
     * @var SetupContext
     */
    private $setupContext;

    /**
     * @param SetupContext                         $setupContext
     * @param Connection                           $connection
     * @param \Shopware_Components_Snippet_Manager $snippetManager
     */
    public function __construct(
        SetupContext $setupContext,
        Connection $connection,
        \Shopware_Components_Snippet_Manager $snippetManager
    ) {
        $this->connection = $connection;
        $this->snippetManager = $snippetManager;
        $this->setupContext = $setupContext;
    }

    /**
     * Updates the default profiles.
     * We won´t update unique profile names and types.
     * Only changes to the profile tree should
     * be made and make sense.
     */
    public function update()
    {
        $sql = '
            UPDATE s_import_export_profile
            SET `tree` = :tree, `description` = :description
            WHERE `name` = :name
        ';

        /** @var ProfileMetaData[] $profiles */
        $profiles = ProfileHelper::getProfileInstances();

        foreach ($profiles as $profile) {
            $serializedTree = json_encode($profile);

            $params = [
                'tree' => $serializedTree,
                'name' => $profile->getName(),
                'description' => $profile->getDescription(),
            ];

            $this->connection->executeQuery($sql, $params);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function isCompatible()
    {
        return $this->setupContext->assertMinimumPluginVersion(self::MIN_PLUGIN_VERSION);
    }
}
