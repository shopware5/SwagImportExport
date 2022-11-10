<?php
declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport\Migrations;

use Shopware\Components\Migrations\AbstractMigration;
use Shopware\Components\Migrations\AbstractPluginMigration;

class Migration4 extends AbstractPluginMigration
{
    public function up($modus): void
    {
        if ($modus === AbstractMigration::MODUS_UPDATE) {
            return;
        }

        $this->connection->exec(<<<SQL
SET @pluginId = (SELECT `id` FROM `s_core_plugins` WHERE name = 'SwagImportExport' LIMIT 1);
UPDATE s_core_menu
SET controller = 'SwagImportExport',
    action = 'index',
    class = 'sprite-arrow-circle-double-135 contents--import-export',
    pluginID = @pluginId,
    active = 1
WHERE name = 'Import/Export'
SQL);
    }

    public function down(bool $keepUserData): void
    {
        $this->connection->exec(<<<SQL
UPDATE s_core_menu
SET controller = 'PluginManager',
    action = 'ImportExport',
    class = 'sprite-arrow-circle-double-135 contents--import-export',
    pluginID = null
WHERE name = 'Import/Export'
SQL);
    }
}
