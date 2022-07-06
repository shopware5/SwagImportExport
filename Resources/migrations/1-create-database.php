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

class Migration1 extends AbstractPluginMigration
{
    public function up($modus): void
    {
        if ($modus === AbstractPluginMigration::MODUS_UPDATE) {
            return;
        }

        $this->connection->exec(<<<SQL
        CREATE TABLE s_import_export_session (id INT AUTO_INCREMENT NOT NULL, profile_id INT DEFAULT NULL, type VARCHAR(200) NOT NULL, ids LONGTEXT NOT NULL, position INT NOT NULL, total_count INT NOT NULL, username VARCHAR(200) DEFAULT NULL, file_name VARCHAR(200) NOT NULL, format VARCHAR(100) NOT NULL, file_size INT DEFAULT NULL, state VARCHAR(100) NOT NULL, created_at DATETIME NOT NULL, INDEX IDX_64E921BACCFA12B8 (profile_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB;
        CREATE TABLE s_import_export_log (id INT AUTO_INCREMENT NOT NULL, session_id INT DEFAULT NULL, message LONGTEXT DEFAULT NULL, state VARCHAR(100) DEFAULT NULL, created_at DATETIME NOT NULL, INDEX IDX_8F9D86BB613FECDF (session_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB;
        CREATE TABLE s_import_export_profile (id INT AUTO_INCREMENT NOT NULL, type VARCHAR(200) NOT NULL, base_profile INT DEFAULT NULL, name VARCHAR(200) NOT NULL, description LONGTEXT DEFAULT NULL, tree LONGTEXT NOT NULL, hidden INT NOT NULL, is_default TINYINT(1) NOT NULL, UNIQUE INDEX UNIQ_35FA5E615E237E06 (name), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB;
        CREATE TABLE s_import_export_expression (id INT AUTO_INCREMENT NOT NULL, profile_id INT DEFAULT NULL, variable VARCHAR(200) NOT NULL, export_conversion LONGTEXT NOT NULL, import_conversion LONGTEXT NOT NULL, INDEX IDX_42CE4B73CCFA12B8 (profile_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB;
        ALTER TABLE s_import_export_session ADD CONSTRAINT FK_64E921BACCFA12B8 FOREIGN KEY (profile_id) REFERENCES s_import_export_profile (id) ON DELETE CASCADE;
        ALTER TABLE s_import_export_log ADD CONSTRAINT FK_8F9D86BB613FECDF FOREIGN KEY (session_id) REFERENCES s_import_export_session (id) ON DELETE SET NULL;
        ALTER TABLE s_import_export_expression ADD CONSTRAINT FK_42CE4B73CCFA12B8 FOREIGN KEY (profile_id) REFERENCES s_import_export_profile (id) ON DELETE CASCADE;
SQL);
    }

    public function down(bool $keepUserData): void
    {
        $this->connection->exec(<<<SQL
            SET foreign_key_checks = 0;
            DROP TABLE s_import_export_expression;
            DROP TABLE s_import_export_log;
            DROP TABLE s_import_export_profile;
            DROP TABLE s_import_export_session;
            SET foreign_key_checks = 1;
SQL);
    }
}
