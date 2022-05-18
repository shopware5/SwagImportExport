<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport\Tests\Integration\DefaultProfiles;

use Doctrine\DBAL\Statement;
use Shopware\Components\DependencyInjection\Container;

trait DefaultProfileImportTestCaseTrait
{
    abstract public function getContainer(): Container;

    private function getImportFile(string $fileName): string
    {
        return __DIR__ . '/Import/_fixtures/' . $fileName;
    }

    /**
     * @param \PDO::FETCH_* $fetchMode
     *
     * @return mixed[]
     */
    private function executeQuery(string $sql, int $fetchMode = \PDO::FETCH_BOTH): array
    {
        /** @var Statement $stmt */
        $stmt = $this->getContainer()->get('dbal_connection')->executeQuery($sql);

        return $stmt->fetchAll($fetchMode);
    }
}
