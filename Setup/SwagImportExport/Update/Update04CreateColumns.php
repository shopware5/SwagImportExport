<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Shopware\Setup\SwagImportExport\Update;

use Doctrine\DBAL\Connection;
use Shopware\Setup\SwagImportExport\SetupContext;

class Update04CreateColumns implements UpdaterInterface
{
    const MAX_PLUGIN_VERSION = '2.0.2';

    /** @var SetupContext  */
    private $setupContext;

    /** @var Connection  */
    private $connection;

    /**
     * @param SetupContext $setupContext
     * @param Connection $connection
     */
    public function __construct(SetupContext $setupContext, Connection $connection)
    {
        $this->setupContext = $setupContext;
        $this->connection = $connection;
    }

    /**
     * @inheritdoc
     */
    public function update()
    {
        try {
            $this->connection->exec('ALTER TABLE s_import_export_profile ADD COLUMN is_default int(1)');
        } catch (\Exception $e) {
        }

        try {
            $this->connection->exec('ALTER TABLE s_import_export_profile ADD COLUMN description text');
        } catch (\Exception $e) {
        }

        try {
            $this->connection->executeQuery('ALTER TABLE s_import_export_profile ADD COLUMN base_profile int');
        } catch (\Exception $e) {
        }
    }

    /**
     * @inheritdoc
     */
    public function isCompatible()
    {
        return version_compare($this->setupContext->getPreviousPluginVersion(), self::MAX_PLUGIN_VERSION, '<');
    }
}
