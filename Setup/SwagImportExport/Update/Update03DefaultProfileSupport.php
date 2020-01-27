<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Shopware\Setup\SwagImportExport\Update;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DBALException;
use Shopware\Setup\SwagImportExport\Exception\DuplicateNameException;
use Shopware\Setup\SwagImportExport\SetupContext;

class Update03DefaultProfileSupport implements UpdaterInterface
{
    const MIN_PLUGIN_VERSION = '2.0.0';

    /**
     * @var Connection
     */
    private $connection;

    /**
     * @var \Shopware_Components_Snippet_Manager
     */
    private $snippetManager;

    /**
     * @var SetupContext
     */
    private $setupContext;

    public function __construct(
        SetupContext $setupContext,
        Connection $connection,
        \Shopware_Components_Snippet_Manager $snippetManager
    ) {
        $this->connection = $connection;
        $this->snippetManager = $snippetManager;
        $this->setupContext = $setupContext;
    }

    /**
     * {@inheritdoc}
     *
     * @throws \Exception
     */
    public function update()
    {
        try {
            $this->connection->executeQuery('ALTER TABLE s_import_export_profile ADD UNIQUE (`name`);');
        } catch (DBALException $exception) {
            if (!$this->isDuplicateNameError($exception)) {
                throw $exception;
            }

            throw new DuplicateNameException($this->snippetManager->getNamespace('backend/swag_importexport/default_profiles')->get('update/duplicate_names'));
        }
    }

    /**
     * {@inheritdoc}
     */
    public function isCompatible()
    {
        return version_compare($this->setupContext->getPreviousPluginVersion(), self::MIN_PLUGIN_VERSION, '<');
    }

    /**
     * @return bool
     */
    private function isDuplicateNameError(\Exception $exception)
    {
        return strpos($exception->getMessage(), 'Duplicate entry') !== false;
    }
}
