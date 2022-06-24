<?php
declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport\Migrations;

use Shopware\Components\Migrations\AbstractPluginMigration;

class Migration6 extends AbstractPluginMigration
{
    public function up($modus): void
    {
        if ($modus === AbstractPluginMigration::MODUS_INSTALL) {
            return;
        }

        $this->connection->exec('UPDATE s_core_config_elements SET value = "b:0" WHERE name = "SwagImportExportImageMode" AND value = "i:1"');
        $this->connection->exec('UPDATE s_core_config_elements SET value = "b:1" WHERE name = "SwagImportExportImageMode" AND value = "i:2"');

        $elementId = $this->connection->query('SELECT id FROM s_core_config_elements WHERE name = "SwagImportExportImageMode"')->fetchColumn();

        if (!\is_string($elementId)) {
            return;
        }

        $this->connection->prepare('UPDATE s_core_config_values SET value = "b:1" WHERE element_id = :elementId and value = "i:2"')->execute(['elementId' => $elementId]);
        $this->connection->prepare('UPDATE s_core_config_values SET value = "b:0" WHERE element_id = :elementId and value = "i:1"')->execute(['elementId' => $elementId]);
    }

    public function down(bool $keepUserData): void
    {
        // @todo: Implement down
    }
}
