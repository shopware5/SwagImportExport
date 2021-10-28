<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport\Tests\Helper;

trait DatabaseTestCaseTrait
{
    /**
     * @before
     */
    protected function startTransactionBefore(): void
    {
        $modelManager = Shopware()->Container()->get('models');
        $modelManager->beginTransaction();
    }

    /**
     * @after
     */
    protected function rollbackTransactionAfter(): void
    {
        $modelManager = Shopware()->Container()->get('models');
        $modelManager->rollback();
    }
}
