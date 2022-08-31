<?php
declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport\Tests\Integration\DefaultProfiles;

use Shopware\Components\DependencyInjection\Container;

trait DefaultProfileImportTestCaseTrait
{
    abstract public function getContainer(): Container;

    private function getImportFile(string $fileName): string
    {
        return __DIR__ . '/Import/_fixtures/' . $fileName;
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    private function executeQuery(string $sql): array
    {
        return $this->getContainer()->get('dbal_connection')->executeQuery($sql)->fetchAllAssociative();
    }
}
