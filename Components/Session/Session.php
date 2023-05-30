<?php
declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport\Components\Session;

use Shopware\Components\Model\ModelManager;
use SwagImportExport\Components\Profile\Profile;
use SwagImportExport\Models\Session as SessionEntity;

class Session
{
    public const SESSION_CLOSE = 'closed';
    public const SESSION_NEW = 'new';
    public const SESSION_ACTIVE = 'active';
    public const SESSION_FINISHED = 'finished';

    private SessionEntity $sessionEntity;

    private ModelManager $manager;

    public function __construct(
        SessionEntity $session,
        ModelManager $manager
    ) {
        $this->sessionEntity = $session;
        $this->manager = $manager;
    }

    /**
     * Returns session entity
     */
    public function getEntity(): SessionEntity
    {
        return $this->sessionEntity;
    }

    /**
     * Check if the session contains ids.
     * If the session has no ids, then the db adapter must be used to retrieve them.
     * Then writes these ids to the session and sets the session state to "active".
     * For now, we will write the ids as a serialized array.
     *
     * @param array<string, mixed> $data
     *
     * @throws \Exception
     */
    public function start(Profile $profile, array $data): void
    {
        if (isset($data['totalCountedIds']) && $data['totalCountedIds'] > 0) {
            // set count
            $this->sessionEntity->setTotalCount($data['totalCountedIds']);
        }
        // set ids
        $this->sessionEntity->setIds($data['serializedIds']);

        // set type
        $this->sessionEntity->setType($data['type']);

        // set position
        $this->sessionEntity->setPosition(0);

        $dateTime = new \DateTime('now');

        // set date/time
        $this->sessionEntity->setCreatedAt($dateTime);

        if (!isset($data['fileName'])) {
            throw new \Exception('Invalid file name.');
        }

        // set fileName
        $this->sessionEntity->setFileName($data['fileName']);

        if (isset($data['fileSize'])) {
            $this->sessionEntity->setFileSize((int) $data['fileSize']);
        }

        if (!isset($data['format'])) {
            throw new \Exception('Invalid format.');
        }

        // set username
        $this->sessionEntity->setUserName($data['username']);

        // set format
        $this->sessionEntity->setFormat($data['format']);

        // change state
        $this->sessionEntity->setState(self::SESSION_ACTIVE);
        if ($this->sessionEntity->getTotalCount() === 0) {
            // If there is no data to be imported or exported, directly finish the session, so the XML footer is correctly written
            $this->sessionEntity->setState(self::SESSION_FINISHED);
        }

        // set profile
        $this->sessionEntity->setProfile($profile->getEntity());

        $this->manager->persist($this->sessionEntity);
        $this->manager->flush();
    }

    /**
     * Checks if the number of processed records has reached the current max records count.
     * If reached then the session state will be set to "stopped"
     * Updates the session position with the current position (stored in a member variable).
     * Updates the file size of the output file
     */
    public function progress(int $step, ?string $file = null): void
    {
        $sessionEntity = $this->getEntity();

        $position = $sessionEntity->getPosition();
        $count = $sessionEntity->getTotalCount();

        $newPosition = $position + $step;

        if ($newPosition >= $count) {
            $sessionEntity->setState(self::SESSION_FINISHED);
            $sessionEntity->setPosition($count);
        } else {
            $sessionEntity->setPosition($newPosition);
        }

        if ($file && \file_exists($file)) {
            $fileSize = \sprintf('%u', \filesize($file));
            $sessionEntity->setFileSize((int) $fileSize);
        }

        $this->manager->merge($sessionEntity);
        $this->manager->flush();
    }

    /**
     * Checks also the current position - if all the ids of the session are done, then the function does nothing.
     * Otherwise, it sets the session state from "suspended" to "active", so that it is ready again for processing.
     *
     * @retrun array{recordIds: array<int>, fileName: string}
     */
    public function resume(): void
    {
        $this->sessionEntity->setState(self::SESSION_ACTIVE);
        $this->manager->persist($this->sessionEntity);
        $this->manager->flush();
    }

    /**
     * Marks the session as closed (sets the session state as "closed").
     * If the session progress has not reached to the end, throws an exception.
     */
    public function close(): void
    {
        $this->sessionEntity->setState(self::SESSION_CLOSE);

        $this->manager->merge($this->sessionEntity);
        $this->manager->flush();
    }

    /**
     * Returns the state of the session.
     * active:
     *     Session is running, and we can read/write records.
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

    public function getFileName(): string
    {
        return $this->getEntity()->getFileName();
    }

    public function setTotalCount(int $totalCount): void
    {
        $this->getEntity()->setTotalCount($totalCount);
    }

    public function getPosition(): int
    {
        return $this->getEntity()->getPosition() ?? 0;
    }

    /**
     * @return array<int>
     */
    public function getRecordIds(): array
    {
        $recordIds = $this->sessionEntity->getIds() ?? '';
        $unserialized = \unserialize($recordIds, []);

        return \is_array($unserialized) ? $unserialized : [];
    }

    /**
     * @param array<int> $ids
     */
    public function setRecordIds(array $ids): void
    {
        $this->sessionEntity->setIds(serialize($ids));
    }
}
