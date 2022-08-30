<?php

declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport\Components\Structs;

use SwagImportExport\Components\Profile\Profile;

class ImportRequest
{
    public string $format;

    public Profile $profileEntity;

    public string $inputFile;

    public string $username = 'Cli';

    public int $batchSize = 50;

    /**
     * @param array<string, mixed> $data
     */
    public function setData(array $data): void
    {
        foreach ($data as $key => $value) {
            if (!property_exists($this, $key)) {
                continue;
            }

            $this->{$key} = $value;
        }
    }
}
