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

class Migration5 extends AbstractPluginMigration
{
    public function up($modus): void
    {
        $profiles = $this->connection->query('SELECT COUNT(id) as count, name FROM s_import_export_profile GROUP BY name')->fetchAll();

        if (!\is_array($profiles)) {
            throw new \RuntimeException('No result');
        }

        foreach ($profiles as $profile) {
            if ((int) $profile['count'] === 1) {
                continue;
            }

            $stm = $this->connection->prepare(
                'SELECT * FROM s_import_export_profile WHERE name = :name ORDER BY id'
            );
            $stm->execute(['name' => $profile['name']]);

            $profilesWithSameName = $stm->fetchAll();

            if (!\is_array($profilesWithSameName)) {
                throw new \RuntimeException('No result');
            }

            $this->addSuffixToProfileNames($profilesWithSameName);
        }
    }

    public function down(bool $keepUserData): void
    {
    }

    /**
     * @param list<array<array-key, mixed>> $profiles
     */
    private function addSuffixToProfileNames(array $profiles): void
    {
        foreach ($profiles as $index => $profile) {
            if ($index === 0) {
                continue;
            }

            $name = \uniqid($profile['name'] . '_', true);

            $this->connection->prepare(
                'UPDATE s_import_export_profile SET name = :name WHERE id=:id and is_default = 0',
            )->execute([
                'name' => $name,
                'id' => $profile['id'],
            ]);
        }
    }
}
