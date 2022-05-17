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
    public function up($modus): void
    {
        $profiles = $this->connection->query('SELECT COUNT(id) as count, name FROM s_import_export_profile GROUP BY name')->fetchAll();

        if (!\is_array($profiles)) {
            throw new \Exception('No result');
        }

        foreach ($profiles as $profile) {
            if ((int) $profile['count'] === 1) {
                continue;
            }

            $profilesWithSameName = $this->connection->query(
                "SELECT * FROM s_import_export_profile WHERE name=${profile['name']} ORDER BY id"
            )->fetchAll();

            if (!\is_array($profilesWithSameName)) {
                throw new \Exception('No result');
            }

            $this->addSuffixToProfileNames($profilesWithSameName);
        }
    }

    public function down(bool $keepUserData): void
    {
        // @todo: Implement down
    }

    /**
     * @param array<int, array{name: string, id: int}> $profiles
     */
    private function addSuffixToProfileNames(array $profiles): void
    {
        foreach ($profiles as $index => $profile) {
            if ($index === 0) {
                continue;
            }

            $name = \uniqid($profile['name'] . '_', true);

            $this->connection->exec(
                "UPDATE s_import_export_profile SET name = ${name} WHERE id=${profile['id']} and is_default = 0",
            );
        }
    }
}
