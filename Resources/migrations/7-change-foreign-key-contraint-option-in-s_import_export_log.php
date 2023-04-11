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

class Migration7 extends AbstractPluginMigration
{
    public function up($modus): void
    {
        $this->connection->exec(<<<SQL
ALTER TABLE s_import_export_log DROP FOREIGN KEY FK_8F9D86BB613FECDF;
SQL);
        $this->connection->exec(<<<SQL
ALTER TABLE s_import_export_log ADD CONSTRAINT FK_8F9D86BB613FECDF FOREIGN KEY (session_id) REFERENCES s_import_export_session (id) ON DELETE CASCADE;
SQL);

        $this->connection->exec(<<<SQL
DELETE FROM s_import_export_log WHERE session_id IS NULL;
SQL);
    }

    public function down(bool $keepUserData): void
    {
    }
}
