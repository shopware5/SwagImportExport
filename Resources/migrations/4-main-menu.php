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
        UPDATE s_core_menu SET controller = 'SwagImportExport', action = 'index', class = 'sprite-arrow-circle-double-135 contents--import-export'  WHERE name = 'Import/Export'

SQL);
    }

    public function down(bool $keepUserData): void
    {
    }
}
