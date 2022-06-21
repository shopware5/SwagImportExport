<?php
declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport\Tests\Helper;

use Shopware\Components\DependencyInjection\Container;

trait DatabaseTestCaseTrait
{
    abstract public function getContainer(): Container;

    /**
     * @before
     */
    protected function startTransactionBefore(): void
    {
        $modelManager = $this->getContainer()->get('models');
        $modelManager->beginTransaction();
    }

    /**
     * @after
     */
    protected function rollbackTransactionAfter(): void
    {
        $modelManager = $this->getContainer()->get('models');
        $modelManager->rollback();
    }
}
