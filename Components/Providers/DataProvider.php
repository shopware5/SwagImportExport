<?php
declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport\Components\Providers;

use SwagImportExport\Components\DataManagers\DataManager;
use SwagImportExport\Components\DbAdapters\DataDbAdapter;

class DataProvider implements \Enlight_Hook
{
    private \Enlight_Event_EventManager $eventManager;

    /**
     * @var iterable<DataDbAdapter>
     */
    private iterable $adapters;

    /**
     * @var iterable<DataManager>
     */
    private iterable $dataManagers;

    /**
     * @param iterable<DataDbAdapter> $adapters
     * @param iterable<DataManager>   $dataManagers
     */
    public function __construct(
        \Enlight_Event_EventManager $eventManager,
        iterable $adapters,
        iterable $dataManagers
    ) {
        $this->eventManager = $eventManager;
        $this->adapters = $adapters;
        $this->dataManagers = $dataManagers;
    }

    /**
     * Returns the necessary adapter
     */
    public function createDbAdapter(string $adapterType): DataDbAdapter
    {
        $event = $this->fireCreateFactoryEvent($adapterType);
        if ($event instanceof \Enlight_Event_EventArgs
            && $event->getReturn() instanceof DataDbAdapter
        ) {
            return $event->getReturn();
        }

        foreach ($this->adapters as $adapter) {
            if ($adapter->supports($adapterType)) {
                return $adapter;
            }
        }

        throw new \RuntimeException('Db adapter type is not valid');
    }

    /**
     * Return necessary data manager
     */
    public function createDataManager(string $managerType): ?DataManager
    {
        foreach ($this->dataManagers as $manager) {
            if ($manager->supports($managerType)) {
                return $manager;
            }
        }

        return null;
    }

    protected function fireCreateFactoryEvent(string $adapterType): ?\Enlight_Event_EventArgs
    {
        return $this->eventManager->notifyUntil(
            'Shopware_Components_SwagImportExport_Factories_CreateDbAdapter',
            ['subject' => $this, 'adapterType' => $adapterType]
        );
    }
}
