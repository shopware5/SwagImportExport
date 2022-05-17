<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Shopware\Setup\SwagImportExport\Install;

use Doctrine\DBAL\Connection;
use Shopware\Setup\SwagImportExport\DefaultProfiles\ProfileHelper;
use Shopware\Setup\SwagImportExport\DefaultProfiles\ProfileMetaData;

class DefaultProfileInstaller
{
    /**
     * @var Connection
     */
    private $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    public function install()
    {
        $sql = '
            INSERT IGNORE INTO s_import_export_profile
            (`type`, `name`, `description`, `tree`, `hidden`, `is_default`)
            VALUES
            (:type, :name, :description, :tree, :hidden, :is_default)
        ';

        /** @var ProfileMetaData[] $profiles */
        $profiles = ProfileHelper::getProfileInstances();

        foreach ($profiles as $profile) {
            $serializedTree = \json_encode($profile);

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
    }
}
