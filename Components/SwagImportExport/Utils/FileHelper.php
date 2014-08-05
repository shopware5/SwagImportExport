<?php

namespace Shopware\Components\SwagImportExport\Utils;

class FileHelper
{

    public function writeStringToFile($file, $content, $flag = null)
    {
        if ($flag === null) {
            $str = @file_put_contents($file, $content);
        } else {
            $str = @file_put_contents($file, $content, $flag);
        }

        if ($str === false) {
            throw new \Exception("Cannot write in '$file'");
        }
    }

}
