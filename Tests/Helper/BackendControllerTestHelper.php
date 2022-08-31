<?php
declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport\Tests\Helper;

use SwagImportExport\Tests\Helper\DataProvider\ProfileDataProvider;

class BackendControllerTestHelper
{
    /**
     * @var array<string>
     */
    private array $files = [];

    public function tearDown(): void
    {
        foreach ($this->files as $file) {
            \unlink($file);
        }
    }

    public function getProfileIdByType(string $type): int
    {
        $profileDataProvider = new ProfileDataProvider(
            Shopware()->Container()->get('dbal_connection')
        );

        return $profileDataProvider->getProfileId($type);
    }

    public function addFile(string $file): void
    {
        $this->files[] = $file;
    }
}
