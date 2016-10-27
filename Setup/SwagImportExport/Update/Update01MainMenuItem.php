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
     * @var ModelManager
     */
    private $modelManager;

    /**
     * @var SetupContext
     */
    private $setupContext;

    /**
     * @param SetupContext $setupContext
     * @param ModelManager $modelManager
     */
    public function __construct(SetupContext $setupContext, ModelManager $modelManager)
    {
        $this->modelManager = $modelManager;
        $this->setupContext = $setupContext;
    }

    /**
     * @inheritdoc
     */
    public function update()
    {
        $mainMenuInstaller = new MainMenuItemInstaller($this->setupContext, $this->modelManager);
        $mainMenuInstaller->install();
    }

    /**
     * @inheritdoc
     */
    public function isCompatible()
    {
        return true;
    }
}
