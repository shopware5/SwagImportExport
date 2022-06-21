<?php
declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport\Components\Session;

use Doctrine\ORM\EntityRepository;
use Shopware\Components\Model\ModelManager;
use SwagImportExport\Components\Profile\Profile;
use SwagImportExport\Components\UploadPathProvider;
use SwagImportExport\CustomModels\Session as SessionEntity;

/**
 * @method int getTotalCount
 */
class Session
{
    protected ?SessionEntity $sessionEntity;

    /**
     * @var EntityRepository<SessionEntity>|null
     */
    protected ?EntityRepository $sessionRepository;

    protected int $sessionId;

    protected ?ModelManager $manager = null;

    public function __construct(SessionEntity $session)
    {
        $this->sessionEntity = $session;
    }

    /**
     * @param array<mixed> $arguments
     */
    public function __call(string $method, array $arguments)
    {
        $session = $this->getEntity();
        if (\method_exists($session, $method)) {
            return $session->$method($arguments);
        }
        throw new \Exception("Method $method does not exists.");
    }

    /**
     * Returns session entity
     */
    public function getEntity(): SessionEntity
    {
        if ($this->sessionEntity === null) {
            $session = $this->getSessionRepository()->findOneBy(['id' => $this->getSessionId()]);
            if (!$session instanceof SessionEntity) {
                throw new \RuntimeException(sprintf('Cannot find %s with ID "%s"', SessionEntity::class, $this->getSessionId()));
            }
            $this->sessionEntity = $session;
        }

        return $this->sessionEntity;
    }

    public function getSessionId(): int
    {
        return $this->sessionId;
    }

    /**
     * Check if the session contains ids.
     * If the session has no ids, then the db adapter must be used to retrieve them.
     * Then writes these ids to the session and sets the session state to "active".
     * For now we will write the ids as a serialized array.
     *
     * @param array<string, mixed> $data
     *
     * @throws \Exception
     */
    public function start(Profile $profile, array $data): void
    {
        $sessionEntity = $this->getEntity();

        if (isset($data['totalCountedIds']) && $data['totalCountedIds'] > 0) {
            // set count
            $sessionEntity->setTotalCount($data['totalCountedIds']);
        }
        // set ids
        $sessionEntity->setIds($data['serializedIds']);

        // set type
        $sessionEntity->setType($data['type']);

        // set position
        $sessionEntity->setPosition(0);

        $dateTime = new \DateTime('now');

        // set date/time
        $sessionEntity->setCreatedAt($dateTime);

        if (!isset($data['fileName'])) {
            throw new \Exception('Invalid file name.');
        }

        /** @var UploadPathProvider $uploadPathProvider */
        $uploadPathProvider = Shopware()->Container()->get('swag_import_export.upload_path_provider');
        // set fileName
        $sessionEntity->setFileName(
            $uploadPathProvider->getFileNameFromPath($data['fileName'])
        );

        if (isset($data['fileSize'])) {
            $sessionEntity->setFileSize((int) $data['fileSize']);
        }

        if (!isset($data['format'])) {
            throw new \Exception('Invalid format.');
        }

        // set username
        $sessionEntity->setUserName($data['username']);

        // set format
        $sessionEntity->setFormat($data['format']);

        // change state
        $sessionEntity->setState('active');

        // set profile
        $sessionEntity->setProfile($profile->getEntity());

        $this->getManager()->persist($sessionEntity);

        $this->getManager()->flush();

        $this->sessionId = $sessionEntity->getId();
    }

    /**
     * Checks if the number of processed records has reached the current max records count.
     * If reached then the session state will be set to "stopped"
     * Updates the session position with the current position (stored in a member variable).
     * Updates the file size of the output file
     */
    public function progress(int $step, string $file = null): void
    {
        $sessionEntity = $this->getEntity();

        $position = $sessionEntity->getPosition();
        $count = $sessionEntity->getTotalCount();

        $newPosition = $position + $step;

        if ($newPosition >= $count) {
            $sessionEntity->setState('finished');
            $sessionEntity->setPosition($count);
        } else {
            $sessionEntity->setPosition($newPosition);
        }

        if ($file && \file_exists($file)) {
            $fileSize = \sprintf('%u', \filesize($file));
            $sessionEntity->setFileSize((int) $fileSize);
        }

        $this->getManager()->merge($sessionEntity);

        $this->getManager()->flush();
    }

    /**
     * Checks also the current position - if all the ids of the session are done, then the function does nothing.
     * Otherwise it sets the session state from "suspended" to "active", so that it is ready again for processing.
     *
     * @retrun array{recordIds: array<int>, fileName: string}
     */
    public function resume(): array
    {
        $sessionEntity = $this->getEntity();

        $recordIds = $sessionEntity->getIds();

        $sessionEntity->setState('active');

        $this->getManager()->persist($sessionEntity);

        $this->getManager()->flush();

        return [
            'recordIds' => empty($recordIds) ? [] : \unserialize($recordIds),
            'fileName' => $sessionEntity->getFileName(),
        ];
    }

    /**
     * Marks the session as closed (sets the session state as "closed").
     * If the session progress has not reached to the end, throws an exception.
     */
    public function close(): void
    {
        $sessionEntity = $this->getEntity();
        $sessionEntity->setState('closed');

        $this->getManager()->merge($sessionEntity);

        $this->getManager()->flush();
    }

    /**
     * Update session username
     *
     * @param ?string $username
     */
    public function setUsername(?string $username): void
    {
        $sessionEntity = $this->getEntity();

        $sessionEntity->setUserName($username);

        $this->getManager()->persist($sessionEntity);
        $this->getManager()->flush();
    }

    /**
     * Returns entity manager
     */
    public function getManager(): ModelManager
    {
        if ($this->manager === null) {
            $this->manager = Shopware()->Models();
        }

        return $this->manager;
    }

    public function getSessionPosition(): ?int
    {
        return $this->getEntity()->getPosition();
    }

    /**
     * Returns the state of the session.
     * active:
     *     Session is running and we can read/write records.
     * stopped:
     *     Session is stopped because we have reached the max number of records per operation.
     * new:
     *     Session is brand new and still has no records ids.
     * finished:
     *     Session is finished but the output file is still not finished (in case of export)
     *     or the final db save is yet not performed (in case of import).
     * closed:
     *     Session is closed, file is fully exported/imported
     */
    public function getState(): string
    {
        return $this->getEntity()->getState();
    }

    public function setTotalCount($totalCount): void
    {
        $this->getEntity()->setTotalCount($totalCount);
    }

    /**
     * Helper Method to get access to the session repository.
     *
     * @return EntityRepository<SessionEntity>
     */
    public function getSessionRepository(): EntityRepository
    {
        if ($this->sessionRepository === null) {
            $this->sessionRepository = Shopware()->Models()->getRepository(SessionEntity::class);
        }

        return $this->sessionRepository;
    }
}
