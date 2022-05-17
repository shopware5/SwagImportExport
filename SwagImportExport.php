<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport;

if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
} else {
    throw new \Exception('Vendor is missing');
}

use Shopware\Components\CacheManager;
use Shopware\Components\Plugin;
use Shopware\Components\Plugin\Context\ActivateContext;
use Shopware\Components\Plugin\Context\DeactivateContext;
use Shopware\Components\Plugin\Context\InstallContext;
use Shopware\Components\Plugin\Context\UninstallContext;
use Shopware\Components\Plugin\Context\UpdateContext;
use Shopware\Resources\Compiler\HookablePass;
use Shopware\Setup\SwagImportExport\Install\DefaultProfileInstaller;
use Shopware\Setup\SwagImportExport\Update\DefaultProfileUpdater;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class SwagImportExport extends Plugin
{
    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container): void
    {
        $container->setParameter('swag_import_export.plugin_dir', $this->getPath());
        $container->addCompilerPass(new HookablePass());

        parent::build($container);
    }

    /**
     * {@inheritdoc}
     */
    public function install(InstallContext $context): void
    {
        /** @var CacheManager $cacheManager */
        $cacheManager = $this->container->get('shopware.cache_manager');
        $cacheManager->clearProxyCache();

        (new DefaultProfileInstaller($this->container->get('dbal_connection')))->install();

        $this->createDirectories();
    }

    /**
     * {@inheritdoc}
     */
    public function update(UpdateContext $context): void
    {
        /** @var CacheManager $cacheManager */
        $cacheManager = $this->container->get('shopware.cache_manager');
        $cacheManager->clearProxyCache();

        (new DefaultProfileUpdater($this->container->get('dbal_connection')))->update();

        $this->createDirectories();
    }

    /**
     * {@inheritdoc}
     */
    public function uninstall(UninstallContext $context): void
    {
    }

    /**
     * {@inheritdoc}
     */
    public function activate(ActivateContext $context): void
    {
    }

    /**
     * {@inheritdoc}
     */
    public function deactivate(DeactivateContext $context): void
    {
    }

    private function createDirectories(): void
    {
        $importCronPath = Shopware()->DocPath() . 'files/import_cron/';
        if (!\file_exists($importCronPath)) {
            \mkdir($importCronPath, 0777, true);
        }

        if (!\file_exists($importCronPath . '.htaccess')) {
            \copy($this->getPath() . '/Setup/SwagImportExport/template', $importCronPath . '/.htaccess');
        }

        $importExportPath = Shopware()->DocPath() . 'files/import_export/';
        if (!\file_exists($importExportPath)) {
            \mkdir($importExportPath, 0777, true);
        }

        if (!\file_exists($importExportPath . '.htaccess')) {
            \copy($this->getPath() . '/Setup/SwagImportExport/template', $importExportPath . '/.htaccess');
        }
    }
}
