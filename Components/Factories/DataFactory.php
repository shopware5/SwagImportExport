<?php
declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport\Components\Factories;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use SwagImportExport\Components\DataIO;
use SwagImportExport\Components\DataManagers\DataManager;
use SwagImportExport\Components\DbAdapters\DataDbAdapter;
use SwagImportExport\Components\Logger\Logger;
use SwagImportExport\Components\Session\Session;
use SwagImportExport\Components\UploadPathProvider;
use SwagImportExport\Components\Utils\DataColumnOptions;
use SwagImportExport\Components\Utils\DataFilter;
use SwagImportExport\Components\Utils\DataLimit;
use SwagImportExport\CustomModels\Session as SessionEntity;

class DataFactory extends \Enlight_Class implements \Enlight_Hook
{
    private EntityRepository $sessionRepository;

    private \Enlight_Event_EventManager $eventManager;

    private UploadPathProvider $uploadPathProvider;

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
        EntityManagerInterface $entityManager,
        iterable $adapters,
        iterable $dataManagers,
        UploadPathProvider $uploadPathProvider
    ) {
        $this->eventManager = $eventManager;
        $this->uploadPathProvider = $uploadPathProvider;
        $this->sessionRepository = $entityManager->getRepository(SessionEntity::class);
        $this->adapters = $adapters;
        $this->dataManagers = $dataManagers;
    }

    public function createDataIO(DataDbAdapter $dbAdapter, Session $dataSession, Logger $logger): DataIO
    {
        $uploadPathProvider = $this->uploadPathProvider;

        return new DataIO($dbAdapter, $dataSession, $logger, $uploadPathProvider);
    }

    /**
     * Returns the necessary adapter
     */
    public function createDbAdapter(string $adapterType): DataDbAdapter
    {
        $event = $this->fireCreateFactoryEvent($adapterType);
        if ($event && $event instanceof \Enlight_Event_EventArgs
            && $event->getReturn() instanceof DataDbAdapter
        ) {
            return $event->getReturn();
        }

        foreach ($this->adapters as $adapter) {
            if ($adapter->supports($adapterType)) {
                return $adapter;
            }
        }

        throw new \Exception('Db adapter type is not valid');
    }

    /**
     * Return necessary data manager
     *
     * @return object dbAdapter
     */
    public function createDataManager(string $managerType): object
    {
        foreach ($this->dataManagers as $manager) {
            if ($manager->supports($managerType)) {
                return $manager;
            }
        }

        throw new \Exception('Data manager not found');
    }

    public function loadSession(array $data): Session
    {
        $sessionId = $data['sessionId'];

        $sessionEntity = $this->sessionRepository->findOneBy(['id' => $sessionId]);

        if (!$sessionEntity) {
            $sessionEntity = new SessionEntity();
        }

        return $this->createSession($sessionEntity);
    }

    /**
     * Returns columnOptions adapter
     */
    public function createColOpts(?string $options): DataColumnOptions
    {
        return new DataColumnOptions($options);
    }

    /**
     * @param array{limit: ?int, offset: ?int} $options
     */
    public function createLimit(array $options): DataLimit
    {
        return new DataLimit($options);
    }

    /**
     * @param array<string, mixed> $filter
     */
    public function createFilter(array $filter): DataFilter
    {
        return new DataFilter($filter);
    }

    protected function fireCreateFactoryEvent(string $adapterType): ?\Enlight_Event_EventArgs
    {
        return $this->eventManager->notifyUntil(
            'Shopware_Components_SwagImportExport_Factories_CreateDbAdapter',
            ['subject' => $this, 'adapterType' => $adapterType]
        );
    }

    protected function createSession(SessionEntity $sessionEntity): Session
    {
        return new Session($sessionEntity);
    }
}
