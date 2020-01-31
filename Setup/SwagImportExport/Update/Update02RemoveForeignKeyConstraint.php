<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Shopware\Setup\SwagImportExport\Update;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Shopware\Components\Model\ModelManager;
use Shopware\Models\Media\Album;
use Shopware\Setup\SwagImportExport\SetupContext;

class Update02RemoveForeignKeyConstraint implements UpdaterInterface
{
    const MAX_PLUGIN_VERSION = '1.2.1';

    /**
     * @var Connection
     */
    private $dbalConnection;

    /**
     * @var ModelManager
     */
    private $modelManager;

    /**
     * @var AbstractSchemaManager
     */
    private $schemaManager;

    /**
     * @var SetupContext
     */
    private $setupContext;

    public function __construct(
        SetupContext $setupContext,
        Connection $dbalConnection,
        ModelManager $modelManager,
        AbstractSchemaManager $schemaManager
    ) {
        $this->modelManager = $modelManager;
        $this->dbalConnection = $dbalConnection;
        $this->schemaManager = $schemaManager;
        $this->setupContext = $setupContext;
    }

    /**
     * {@inheritdoc}
     */
    public function update()
    {
        try {
            $constraint = $this->getForeignKeyConstraint('s_import_export_session', 'log_id');
            $this->dbalConnection->executeQuery('ALTER TABLE s_import_export_session DROP FOREIGN KEY ' . $constraint);
        } catch (\Exception $exception) {
        }
        $this->dbalConnection->executeQuery('ALTER TABLE s_import_export_session DROP COLUMN log_id');
        $this->removeImportFilesAlbum();
    }

    /**
     * {@inheritdoc}
     */
    public function isCompatible()
    {
        return version_compare($this->setupContext->getPreviousPluginVersion(), self::MAX_PLUGIN_VERSION, '<=')
            && version_compare($this->setupContext->getPluginVersion(), '2.0.0', '<');
    }

    /**
     * @param string $table
     * @param string $column
     *
     * @throws \Exception
     *
     * @return string
     */
    private function getForeignKeyConstraint($table, $column)
    {
        $keys = $this->schemaManager->listTableForeignKeys($table);

        foreach ($keys as $key) {
            if (in_array($column, $key->getLocalColumns(), false)) {
                return $key->getName();
            }
        }
        throw new \Exception('Foreign key constraint not found.');
    }

    /**
     * Removes the import files on update to version 1.2.2
     */
    private function removeImportFilesAlbum()
    {
        $repo = $this->modelManager->getRepository(Album::class);
        $album = $repo->findOneBy(['name' => 'ImportFiles']);
        if ($album) {
            $this->modelManager->remove($album);
            $this->modelManager->flush();
        }
    }
}
