<?php
declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport\Components\Session;

use Doctrine\Persistence\ObjectRepository;
use Shopware\Components\Model\ModelManager;
use SwagImportExport\Components\Profile\Profile;
use SwagImportExport\Components\Structs\ExportRequest;
use SwagImportExport\Components\Structs\ImportRequest;
use SwagImportExport\Components\Utils\SnippetsHelper;
use SwagImportExport\Models\Session as SessionEntity;

class SessionService
{
    /**
     * @var \SwagImportExport\Models\SessionRepository
     */
    private ObjectRepository $sessionRepository;

    private ModelManager $modelManager;

    public function __construct(ModelManager $modelManager)
    {
        $this->sessionRepository = $modelManager->getRepository(SessionEntity::class);
        $this->modelManager = $modelManager;
    }

    public function createSession(): Session
    {
        return new Session(new SessionEntity(), $this->modelManager);
    }

    public function resumeSession(int $sessionId): Session
    {
        $sessionEntity = $this->sessionRepository->findOneBy(['id' => $sessionId]);

        if (!$sessionEntity instanceof \SwagImportExport\Models\Session) {
            throw new \Exception('Session is not existing');
        }

        return new Session($sessionEntity, $this->modelManager);
    }

    public function loadSession(?int $sessionId = null): Session
    {
        if (\is_int($sessionId)) {
            return $this->resumeSession($sessionId);
        }

        return $this->createSession();
    }

    public function startImportSession(ImportRequest $importRequest, Profile $profile, Session $session, int $fileSize): void
    {
        $sessionData = [
            'type' => 'import',
            'fileName' => $importRequest->inputFileName,
            'format' => $importRequest->format,
            'serializedIds' => '',
            'username' => $importRequest->username,
            'fileSize' => $fileSize,
        ];

        $session->start($profile, $sessionData);
    }

    /**
     * @param array<int> $ids
     */
    public function startExportSession(ExportRequest $exportRequest, Profile $profile, Session $session, array $ids): void
    {
        if (empty($ids)) {
            $message = SnippetsHelper::getNamespace()
                ->get('dataio/no_export_records', 'No records found to be exported');
            throw new \Exception($message);
        }

        $sessionData = [
            'type' => 'export',
            'fileName' => $exportRequest->filePath,
            'format' => $exportRequest->format,
            'username' => $exportRequest->username,
            'serializedIds' => \serialize($ids),
            'totalCountedIds' => \count($ids),
        ];

        $session->start($profile, $sessionData);
    }
}
