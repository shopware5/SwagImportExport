<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport\Tests\Integration\DefaultProfiles;

use Doctrine\DBAL\Statement;

trait DefaultProfileImportTestCaseTrait
{
    /**
     * @param string $fileName
     * @return string
     */
    private function getImportFile($fileName)
    {
        return __DIR__ . '/Import/_fixtures/' . $fileName;
    }

    /**
     * @param string $sql
     * @return mixed
     */
    private function executeQuery($sql)
    {
        /** @var Statement $stmt */
        $stmt = Shopware()->Container()->get('dbal_connection')->executeQuery($sql);
        return $stmt->fetchAll();
    }
}
