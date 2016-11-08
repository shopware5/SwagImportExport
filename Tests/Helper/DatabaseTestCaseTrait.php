<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport\Tests\Helper;

use Shopware\Components\Model\ModelManager;

trait DatabaseTestCaseTrait
{
    /**
     * @before
     */
    protected function startTransactionBefore()
    {
        /** @var ModelManager $modelManager */
        $modelManager = Shopware()->Container()->get('models');
        $modelManager->beginTransaction();
    }

    /**
     * @after
     */
    protected function rollbackTransactionAfter()
    {
        /** @var ModelManager $modelManager */
        $modelManager = Shopware()->Container()->get('models');
        $modelManager->rollback();
    }
}
