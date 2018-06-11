<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Shopware\Setup\SwagImportExport\Update;

use Shopware\Components\Model\ModelManager;
use Shopware\Setup\SwagImportExport\Install\MainMenuItemInstaller;
use Shopware\Setup\SwagImportExport\SetupContext;

class Update01MainMenuItem implements UpdaterInterface
{
    /**
     * @var MainMenuItemInstaller
     */
    private $mainMenuItemInstaller;

    /**
     * @param SetupContext $setupContext
     * @param ModelManager $modelManager
     */
    public function __construct(SetupContext $setupContext, ModelManager $modelManager)
    {
        $this->mainMenuItemInstaller = new MainMenuItemInstaller($setupContext, $modelManager);
    }

    /**
     * {@inheritdoc}
     */
    public function update()
    {
        $this->mainMenuItemInstaller->install();
    }

    /**
     * {@inheritdoc}
     */
    public function isCompatible()
    {
        return $this->mainMenuItemInstaller->isCompatible();
    }
}
