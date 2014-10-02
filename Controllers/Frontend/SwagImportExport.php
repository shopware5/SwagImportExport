<?php

/**
 * Shopware 4
 * Copyright © shopware AG
 *
 * According to our dual licensing model, this program can be used either
 * under the terms of the GNU Affero General Public License, version 3,
 * or under a proprietary license.
 *
 * The texts of the GNU Affero General Public License with an additional
 * permission and of our proprietary license can be found at and
 * in the LICENSE file you have received along with this program.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * "Shopware" is a registered trademark of shopware AG.
 * The licensing of the program under the AGPLv3 does not imply a
 * trademark license. Therefore any rights, title and interest in
 * our trademarks remain entirely with us.
 */
use \Shopware\Components\SwagImportExport\Utils\SnippetsHelper as SnippetsHelper;

/**
 * Shopware ImportExport Plugin
 *
 * @category Shopware
 * @package Shopware\Plugins\SwagImportExport
 * @copyright Copyright (c) shopware AG (http://www.shopware.de)
 */
class Shopware_Controllers_Frontend_SwagImportExport extends Enlight_Controller_Action
{
    
    public function init()
    {
        Shopware()->Plugins()->Backend()->Auth()->setNoAuth();
        Shopware()->Plugins()->Controller()->ViewRenderer()->setNoRender();
    }
    
    /**
     * Custom cronjob for import
     * 
     * @return boolean
     */
    public function cronAction()
    {        
        $directory = Shopware()->DocPath() . 'files/import_cron/';
        $files = scandir($directory);

        $lockerFilename = '__running';
        $lockerFileLocation = $directory . $lockerFilename;
        
        if ($files === false) {
            return false;
        }
        
        if (in_array($lockerFilename, $files)) {
            echo "There is runnig import at the moment";
            return;
        }
        
        //Create empty file to flag cron as running
        fopen($lockerFileLocation, "w");

        $manager = Shopware()->Models();
        $profileRepository = $manager->getRepository('Shopware\CustomModels\ImportExport\Profile');
        foreach($files as $file) {
            $type = strtolower(pathinfo($file, PATHINFO_EXTENSION));
            if ($type == 'xml' || $type == 'csv') {
                try {
                    
                    $profile = \Shopware\Components\SwagImportExport\Utils\CommandHelper::findProfileByName($file, $profileRepository);
                    if ($profile === false) {
                        $message = SnippetsHelper::getNamespace()->get('cronjob/no_profile', 'Failed to create directory %s');
                        throw new \Exception(sprintf($message, $file));
                    }

                    $filePath = Shopware()->DocPath() . 'files/import_cron/' . $file;
                    $fileObject = new \Symfony\Component\HttpFoundation\File\File($filePath);

                    $album = $this->albumCreate();
                    
                    $media = new \Shopware\Models\Media\Media();

                    $media->setAlbum($album);
                    $media->setDescription('');
                    $media->setCreated(new DateTime());
                    $media->setExtension($type);

                    $identity = Shopware()->Auth()->getIdentity();
                    if ($identity !== null) {
                        $media->setUserId($identity->id);
                    } else {
                        $media->setUserId(0);
                    }

                    //set the upload file into the model. The model saves the file to the directory
                    $media->setFile($fileObject);

                    Shopware()->Models()->persist($media);
                    Shopware()->Models()->flush();

                    $mediaPath = $media->getPath();

                    $commandHelper = new \Shopware\Components\SwagImportExport\Utils\CommandHelper(array(
                        'profileEntity' => $profile,
                        'filePath' => $mediaPath,
                        'format' => $type,
                    ));
                
                } catch (\Exception $e) {
                    echo $e->getMessage() . "\n";
                    return;
                }

                try {
                    $return = $commandHelper->prepareImport();
                    $count = $return['count'];

                    $return = $commandHelper->importAction();
                    $position = $return['data']['position'];

                    while ($position < $count) {
                        $return = $commandHelper->importAction();
                        $position = $return['data']['position'];
                    }
                    
                } catch (\Exception $e) {
                    // copy file as broken
                    copy($mediaPath, Shopware()->DocPath() . 'files/import_export/broken-' . $file);
                    echo $e->getMessage() . "\n";
                    return ;
                }
            }
        }
        
        unlink($lockerFileLocation);
    }
    
    /**
     * Create album in media manager for ImportFiles
     * 
     * @return \Shopware\Models\Media\Album
     */
    protected function albumCreate()
    {
        $albumRepo = Shopware()->Models()->getRepository('Shopware\Models\Media\Album');
        $album = $albumRepo->findOneBy(array('name' => 'ImportFiles'));
        
        if (!$album) {
            $album = new Shopware\Models\Media\Album();
            $album->setName('ImportFiles');
            $album->setPosition(0);
            Shopware()->Models()->persist($album);
            Shopware()->Models()->flush($album);
        }
        
        return $album;
    }
    
}
