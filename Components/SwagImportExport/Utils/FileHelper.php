<?php

namespace Shopware\Components\SwagImportExport\Utils;

class FileHelper
{
    /**
     * @param string $file
     * @param string $content
     * @param int|null $flag
     * @throws \Exception
     */
    public function writeStringToFile($file, $content, $flag = null)
    {
        try {
            file_put_contents($file, $content, $flag);
        } catch (\Exception $e) {
            throw new \Exception("Cannot write in '$file'");
        }
    }
}
