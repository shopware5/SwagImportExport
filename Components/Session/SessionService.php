<?php
declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport\Components\Session;

use Doctrine\Instantiator\Exception\UnexpectedValueException;
use Shopware\Components\Model\ModelManager;
use SwagImportExport\Components\Profile\Profile;
use SwagImportExport\Components\Structs\ExportRequest;
use SwagImportExport\Components\Structs\ImportRequest;
use SwagImportExport\Components\UploadPathProvider;
use SwagImportExport\Models\Session as SessionEntity;
use SwagImportExport\Models\SessionRepository;

class SessionService
{
    private SessionRepository $sessionRepository;

    private ModelManager $modelManager;

    private UploadPathProvider $uploadPathProvider;

    public function __construct(
        ModelManager $modelManager,
        UploadPathProvider $uploadPathProvider
    ) {
        $sessionRepository = $modelManager->getRepository(SessionEntity::class);
        if (!$sessionRepository instanceof SessionRepository) {
            throw new UnexpectedValueException(\sprintf('Expect SessionRepository got %s', \gettype($sessionRepository)));
        }

        $this->sessionRepository = $sessionRepository;
        $this->modelManager = $modelManager;
        $this->uploadPathProvider = $uploadPathProvider;
    }

    public function createSession(): Session
    {
        return new Session(new SessionEntity(), $this->modelManager);
    }

    public function loadSession(?int $sessionId = null): Session
    {
        if (\is_int($sessionId)) {
            return $this->resumeSession($sessionId);
        }

        return $this->createSession();
    }

    public function startImportSession(
        ImportRequest $importRequest,
        Profile $profile,
        Session $session,
        int $fileSize
    ): void {
        $session->start($profile, [
            'type' => 'import',
            'fileName' => $this->uploadPathProvider->getFileNameFromPath($importRequest->inputFile),
            'format' => $importRequest->format,
            'serializedIds' => '',
            'username' => $importRequest->username,
            'fileSize' => $fileSize,
        ]);
    }

    /**
     * @param array<int> $ids
     */
    public function startExportSession(
        ExportRequest $exportRequest,
        Profile $profile,
        Session $session,
        array $ids
    ): void {
        $session->start($profile, [
            'type' => 'export',
            'fileName' => $this->uploadPathProvider->getFileNameFromPath($exportRequest->filePath),
            'format' => $exportRequest->format,
            'username' => $exportRequest->username,
            'serializedIds' => \serialize($ids),
            'totalCountedIds' => \count($ids),
        ]);
    }

    private function resumeSession(int $sessionId): Session
    {
        $sessionEntity = $this->sessionRepository->findOneBy(['id' => $sessionId]);

        if (!$sessionEntity instanceof SessionEntity) {
            throw new \Exception('Session is not existing');
        }

        return new Session($sessionEntity, $this->modelManager);
    }
}
