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

use Shopware\Components\SwagImportExport\Utils\SnippetsHelper;
use Shopware\Components\SwagImportExport\Utils\CommandHelper;

/**
 * Shopware ImportExport Plugin
 *
 * @category Shopware
 * @copyright Copyright (c) shopware AG (http://www.shopware.de)
 */
class Shopware_Controllers_Backend_SwagImportExportCron extends Shopware_Controllers_Backend_ExtJs
{

	/**
	 * Shopware\Components\Model\ModelManager
	 */
	protected $manager;

	public function init()
	{
		Shopware()->Plugins()->Backend()->Auth()->setNoAuth();
		Shopware()->Plugins()->Controller()->ViewRenderer()->setNoRender();
	}

	/**
	 * Check for terminal call on cron action
	 */
	public function preDispatch()
	{
		//Call cron only if request is not from browser
		if (php_sapi_name() == 'cli') {
			$this->cronAction();
		}
	}

	/**
	 * Custom cronjob for import
	 *
	 * @return boolean
	 */
	public function cronAction()
	{
		$directory = Shopware()->DocPath() . 'files/import_cron/';
		$allFiles = scandir($directory);
		$files = array_diff($allFiles, array('.', '..'));

		$lockerFilename = '__running';
		$lockerFileLocation = $directory . $lockerFilename;

		if (in_array($lockerFilename, $files)) {
			$file = fopen($lockerFileLocation, "r");
			$fileContent = (int) fread($file, filesize($lockerFileLocation));
			fclose($file);

			if ($fileContent > time()) {
				echo "There is already an import in progess.\n";
				return;
			} else {
				unlink($lockerFileLocation);
			}
		}

		if ($files === false || count($files) == 0) {
			echo "No import files are found\n";
			return;
		}

		//Create empty file to flag cron as running
		$timeout = time() + 1800;
		$file = fopen($lockerFileLocation, "w");
		fwrite($file, $timeout);
		fclose($file);

		$profileRepository = $this->getManager()->getRepository('Shopware\CustomModels\ImportExport\Profile');
        foreach ($files as $file) {
            $fileExtension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
            $fileName = strtolower(pathinfo($file, PATHINFO_FILENAME));

            if ($fileExtension == 'xml' || $fileExtension == 'csv') {
                try {

                    $profile = CommandHelper::findProfileByName($file, $profileRepository);
                    if ($profile === false) {
                        $message = SnippetsHelper::getNamespace()->get('cronjob/no_profile', 'No profile found %s');
                        throw new \Exception(sprintf($message, $fileName));
                    }

                    $filePath = Shopware()->DocPath() . 'files/import_cron/' . $file;
                    $fileObject = new \Symfony\Component\HttpFoundation\File\File($filePath);

                    $album = $this->createAlbum();

                    $media = new \Shopware\Models\Media\Media();

                    $media->setAlbum($album);
                    $media->setDescription('');
                    $media->setCreated(new DateTime());
                    $media->setExtension($fileExtension);

                    $identity = Shopware()->Auth()->getIdentity();
                    if ($identity !== null) {
                        $media->setUserId($identity->id);
                    } else {
                        $media->setUserId(0);
                    }

                    //set the upload file into the model. The model saves the file to the directory
                    $media->setFile($fileObject);

                    $this->getManager()->persist($media);
                    $this->getManager()->flush();

                    $mediaPath = $media->getPath();

                } catch (\Exception $e) {
                    echo $e->getMessage() . "\n";
                    unlink($lockerFileLocation);
                    return;
                }

                try {

                    $return = $this->start($profile, $mediaPath, $fileExtension);

                    $profilesMapper = array('articles', 'articlesImages');

                    //loops the unprocessed data
                    $pathInfo = pathinfo($mediaPath);
                    foreach ($profilesMapper as $profileName) {
                        $tmpFileName = 'media/unknown/' . $pathInfo['filename'] . '-' . $profileName . '-tmp.csv';
                        $tmpFile = Shopware()->DocPath() . $tmpFileName;

                        if (file_exists($tmpFile)) {
                            $outputFile = str_replace('-tmp', '-swag', $tmpFile);
                            rename($tmpFile, $outputFile);

                            $profile = $this->Plugin()->getProfileFactory()->loadHiddenProfile($profileName);
                            $profileEntity = $profile->getEntity();

                            $this->start($profileEntity, $outputFile, 'csv');
                        }
                    }

					$message = $return['data']['position'] . ' ' . $return['data']['adapter'] . " imported successfully \n";
					echo $message;
				} catch (\Exception $e) {
					// copy file as broken
					copy($mediaPath, Shopware()->DocPath() . 'files/import_export/broken-' . $file);
					echo $e->getMessage() . "\n";
					unlink($lockerFileLocation);
					return;
				}
			}
		}

		unlink($lockerFileLocation);
	}

	/**
	 * @param \Shopware\CustomModels\ImportExport\Profile $profileModel
	 * @param string $inputFile
	 * @param string $format
	 * @return array
	 */
	protected function start($profileModel, $inputFile, $format)
	{
		$commandHelper = new CommandHelper(array(
				'profileEntity' => $profileModel,
				'filePath' => $inputFile,
				'format' => $format,
				'username' => 'Cron'
		));

		$return = $commandHelper->prepareImport();
		$count = $return['count'];

		$return = $commandHelper->importAction();
		$position = $return['data']['position'];

		while ($position < $count) {
			$return = $commandHelper->importAction();
			$position = $return['data']['position'];
		}

		return $return;
	}

	/**
	 * Creates album in media manager for ImportFiles
	 *
	 * @return \Shopware\Models\Media\Album
	 */
	protected function createAlbum()
	{
		$albumRepo = Shopware()->Models()->getRepository('Shopware\Models\Media\Album');
		$album = $albumRepo->findOneBy(array('name' => 'ImportFiles'));

		if (!$album) {
			$album = new Shopware\Models\Media\Album();
			$album->setName('ImportFiles');
			$album->setPosition(0);
			$this->getManager()->persist($album);
			$this->getManager()->flush($album);
		}

		return $album;
	}

	protected function Plugin()
	{
		return Shopware()->Plugins()->Backend()->SwagImportExport();
	}

	public function getManager()
	{
		if ($this->manager === null) {
			$this->manager = Shopware()->Models();
		}

		return $this->manager;
	}

}