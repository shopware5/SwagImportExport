<?php

namespace Shopware\Components\SwagImportExport\Utils;

use Shopware\Bundle\MediaBundle\MediaService;

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
        /** @var MediaService $mediaService */
        $mediaService = Shopware()->Container()->get('shopware_media.media_service');

        if ($flag === null) {
            $str = $mediaService->write($file, $content);
        } else {
            $content = $mediaService->read($file) . $content;
            $str = $mediaService->write($file, $content);
        }

        if ($str === false) {
            throw new \Exception("Cannot write in '$file'");
        }
    }
}
