<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport\Migrations;

use Shopware\Components\Migrations\AbstractPluginMigration;

class Migration4 extends AbstractPluginMigration
{
    public const CURRENT_MENU_LABEL = 'Import/Export';
    public const CURRENT_MENU_ITEM_CLASS = 'sprite-arrow-circle-double-135 contents--import-export';

    public function up($modus): void
    {
        if ($modus === AbstractPluginMigration::MODUS_UPDATE) {
            return;
        }

        $this->connection->exec(<<<SQL
        UPDATE s_core_menu SET controller = 'SwagImportExport', action = 'index', class = 'sprite-arrow-circle-double-135 contents--import-export'  WHERE name = 'Import/Export'

SQL);
    }

    public function down(bool $keepUserData): void
    {
        // @todo: Implement down
    }
}
