<?php
declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport\Components\Utils;

class FileHelper
{
    /**
     * @throws \Exception
     */
    public function writeStringToFile(string $file, ?string $content, int $flag = 0): void
    {
        \file_put_contents($file, $content, $flag);
    }
}
