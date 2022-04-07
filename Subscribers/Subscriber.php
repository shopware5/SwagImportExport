<?php
declare(strict_types=1);

namespace Shopware\Subscribers;

use Enlight\Event\SubscriberInterface;
use Shopware\Components\SwagImportExport\Factories\DataFactory;
use Shopware\Components\SwagImportExport\Factories\DataTransformerFactory;
use Shopware\Components\SwagImportExport\Factories\FileIOFactory;
use Shopware\Components\SwagImportExport\Factories\ProfileFactory;
use Shopware\Components\SwagImportExport\FileIO\CsvFileWriter;
use Shopware\Components\SwagImportExport\Logger\Logger;
use Shopware\Components\SwagImportExport\Service\AutoImportService;
use Shopware\Components\SwagImportExport\Service\AutoImportServiceInterface;
use Shopware\Components\SwagImportExport\Service\ExportService;
use Shopware\Components\SwagImportExport\Service\ImportService;
use Shopware\Components\SwagImportExport\Service\ProfileService;
use Shopware\Components\SwagImportExport\Service\UnderscoreToCamelCaseService;
use Shopware\Components\SwagImportExport\UploadPathProvider;
use Shopware\Components\SwagImportExport\Utils\FileHelper;
use Symfony\Component\DependencyInjection\ContainerInterface;

class Subscriber implements SubscriberInterface
{
    private ContainerInterface $container;

    private ProfileFactory $profileFactory;

    private FileIOFactory $fileIOFactory;

    private DataFactory $dataFactory;

    private DataTransformerFactory $dataTransformerFactory;

    public function __construct(
        ContainerInterface $container,
        ProfileFactory $profileFactory,
        FileIOFactory $fileIOFactory,
        DataFactory $dataFactory,
        DataTransformerFactory $dataTransformerFactory
    )
    {
        $this->container = $container;
        $this->profileFactory = $profileFactory;
        $this->fileIOFactory = $fileIOFactory;
        $this->dataFactory = $dataFactory;
        $this->dataTransformerFactory = $dataTransformerFactory;
    }

    public static function getSubscribedEvents(): array
    {
        return [
          'Shopware_CronJob_CronAutoImport' => 'onCronImport',
          'Enlight_Bootstrap_InitResource_swag_import_export.upload_path_provider' => 'registerUploadPathProvider',
          'Enlight_Bootstrap_InitResource_swag_import_export.auto_importer' => 'registerAutoImportService',
          'Enlight_Bootstrap_InitResource_swag_import_export.logger' => 'registerLogger',
          'Enlight_Bootstrap_InitResource_swag_import_export.import_service' => 'registerImportService',
          'Enlight_Bootstrap_InitResource_swag_import_export.export_service' => 'registerExportService',
          'Enlight_Bootstrap_InitResource_swag_import_export.profile_service' => 'registerProfileService',
          'Enlight_Bootstrap_InitResource_swag_import_export.csv_file_reader' => 'registerCsvFileReader',
          'Enlight_Bootstrap_InitResource_swag_import_export.csv_file_writer' => 'registerCsvFileWriter',
          'Enlight_Bootstrap_InitResource_swag_import_export.underscore_camelcase_service' => 'registerUnderscoreToCamelCaseService'
        ];
    }

    public function onCronImport()
    {
        /** @var AutoImportServiceInterface $importService */
        $importService = $this->container->get('swag_import_export.auto_importer');
        $importService->runAutoImport();
    }

    /**
     * @return UploadPathProvider
     */
    public function registerUploadPathProvider()
    {
        return new UploadPathProvider(Shopware()->DocPath());
    }

    /**
     * @return AutoImportService
     */
    public function registerAutoImportService()
    {
        return new AutoImportService(
            $this->container->get('swag_import_export.upload_path_provider'),
            $this->container->get('models'),
            $this->profileFactory
        );
    }

    /**
     * @return Logger
     */
    public function registerLogger()
    {
        return new Logger(
            $this->container->get('swag_import_export.csv_file_writer'),
            $this->container->get('models'),
            $this->container->getParameter('shopware.app.rootDir') . 'var/log'
        );
    }


    /**
     * @return ImportService
     */
    public function registerImportService()
    {
        return new ImportService(
            $this->profileFactory,
            $this->fileIOFactory,
            $this->dataFactory,
            $this->dataTransformerFactory,
            $this->container->get('swag_import_export.logger'),
            $this->container->get('swag_import_export.upload_path_provider'),
            $this->container->get('auth'),
            $this->container->get('shopware_media.media_service')
        );
    }


    /**
     * @return ExportService
     */
    public function registerExportService()
    {
        return new ExportService(
            $this->profileFactory,
            $this->fileIOFactory,
            $this->dataFactory,
            $this->dataTransformerFactory,
            $this->container->get('swag_import_export.logger'),
            $this->container->get('swag_import_export.upload_path_provider'),
            $this->container->get('auth'),
            $this->container->get('shopware_media.media_service')
        );
    }


    /**
     * @return ProfileService
     */
    public function registerProfileService()
    {
        return new ProfileService(
            $this->container->get('models'),
            new Symfony\Component\Filesystem\Filesystem(),
            $this->container->get('snippets')
        );
    }


    /**
     * @return CsvFileWriter
     */
    public function registerCsvFileWriter()
    {
        return new CsvFileWriter(
            new FileHelper()
        );
    }


    /**
     * @return UnderscoreToCamelCaseService
     */
    public function registerUnderscoreToCamelCaseService()
    {
        return new UnderscoreToCamelCaseService();
    }

}
