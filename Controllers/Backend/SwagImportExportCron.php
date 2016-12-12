<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Shopware\Components\SwagImportExport\Factories\ProfileFactory;
use Shopware\Components\SwagImportExport\UploadPathProvider;
use Shopware\Components\SwagImportExport\Utils\SnippetsHelper;
use Shopware\Components\SwagImportExport\Utils\CommandHelper;
use Shopware\CustomModels\ImportExport\Profile;
use \Shopware\Components\CSRFWhitelistAware;

/**
 * This is a controller and not a correct implementation of a Shopware cron job. By implementing the cron job as
 * a controller the execution of other cron jobs will not be triggered.
 */
class Shopware_Controllers_Backend_SwagImportExportCron extends Shopware_Controllers_Backend_ExtJs implements CSRFWhitelistAware
{
    /** @var ProfileFactory */
    protected $profileFactory;

    /**
     * @param Enlight_Controller_Request_Request $request
     * @param Enlight_Controller_Response_Response $response
     */
    public function __construct(Enlight_Controller_Request_Request $request, Enlight_Controller_Response_Response $response)
    {
        parent::__construct($request, $response);

        $this->profileFactory = Shopware()->Plugins()->Backend()->SwagImportExport()->getProfileFactory();
    }

    /**
     * @inheritdoc
     */
    public function getWhitelistedCSRFActions()
    {
        return [
            'cron'
        ];
    }

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
     */
    public function cronAction()
    {
        /** @var UploadPathProvider $uploadPathProvider */
        $uploadPathProvider = $this->get('swag_import_export.upload_path_provider');
        $directory = $uploadPathProvider->getPath(UploadPathProvider::CRON_DIR);

        $allFiles = scandir($directory);
        $files = array_diff($allFiles, ['.', '..']);

        $lockerFilename = '__running';
        $lockerFileLocation = $directory . $lockerFilename;

        if (in_array($lockerFilename, $files)) {
            $file = fopen($lockerFileLocation, "r");
            $fileContent = (int) fread($file, filesize($lockerFileLocation));
            fclose($file);

            if ($fileContent > time()) {
                echo "There is already an import in progress.\n";

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

        $manager = $this->getModelManager();
        $profileRepository = $manager->getRepository(Profile::class);
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

                    $mediaPath = $uploadPathProvider->getRealPath($file, UploadPathProvider::CRON_DIR);
                } catch (\Exception $e) {
                    echo $e->getMessage() . "\n";
                    unlink($lockerFileLocation);

                    return;
                }

                try {
                    $return = $this->start($profile, $mediaPath, $fileExtension);

                    $profilesMapper = ['articles', 'articlesImages'];

                    //loops the unprocessed data
                    $pathInfo = pathinfo($mediaPath);
                    foreach ($profilesMapper as $profileName) {
                        $tmpFile = $uploadPathProvider->getRealPath(
                            $pathInfo['filename'] . '-' . $profileName . '-tmp.csv',
                            UploadPathProvider::CRON_DIR
                        );

                        if (file_exists($tmpFile)) {
                            $outputFile = str_replace('-tmp', '-swag', $tmpFile);
                            rename($tmpFile, $outputFile);

                            $profile = $this->profileFactory->loadHiddenProfile($profileName);
                            $profileEntity = $profile->getEntity();

                            $this->start($profileEntity, $outputFile, 'csv');
                        }
                    }

                    $message = $return['data']['position'] . ' ' . $return['data']['adapter'] . " imported successfully \n";
                    echo $message;
                    unlink($mediaPath);
                } catch (\Exception $e) {
                    // copy file as broken
                    $brokenFilePath = $uploadPathProvider->getRealPath('broken-' . $file, UploadPathProvider::DIR);
                    copy($mediaPath, $brokenFilePath);

                    echo $e->getMessage() . "\n";
                    unlink($lockerFileLocation);
                    return;
                }
            }
        }

        unlink($lockerFileLocation);
    }

    /**
     * @param bool|Profile $profileModel
     * @param string $inputFile
     * @param string $format
     * @return array
     */
    protected function start($profileModel, $inputFile, $format)
    {
        $commandHelper = new CommandHelper(
            [
                'profileEntity' => $profileModel,
                'filePath' => $inputFile,
                'format' => $format,
                'username' => 'Cron'
            ]
        );

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
}
