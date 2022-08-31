<?php
declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport\Components\Service;

use SwagImportExport\Components\DataIO;
use SwagImportExport\Components\Logger\LoggerInterface;
use SwagImportExport\Components\Providers\DataProvider;
use SwagImportExport\Components\Session\Session;
use SwagImportExport\Components\Structs\ExportRequest;
use SwagImportExport\Components\Utils\SnippetsHelper;

class ExportService implements ExportServiceInterface
{
    private DataProvider $dataProvider;

    private LoggerInterface $logger;

    private DataWorkflow $dataWorkflow;

    public function __construct(
        DataProvider $dataProvider,
        LoggerInterface $logger,
        DataWorkflow $dataWorkflow
    ) {
        $this->dataProvider = $dataProvider;
        $this->logger = $logger;
        $this->dataWorkflow = $dataWorkflow;
    }

    public function prepareExport(ExportRequest $request, Session $session): int
    {
        $dbAdapter = $this->dataProvider->createDbAdapter($request->profileEntity->getType());
        $recordIds = (new DataIO($dbAdapter, $session, $this->logger))->preloadRecordIds($request, $session);

        return \count($recordIds);
    }

    /**
     * @return \Generator<int>
     */
    public function export(ExportRequest $request, Session $session): \Generator
    {
        try {
            do {
                $position = $this->dataWorkflow->export($request, $session);
                yield $position;
            } while ($session->getState() !== Session::SESSION_CLOSE);

            $message = \sprintf(
                '%s %s %s',
                $position,
                SnippetsHelper::getNamespace('backend/swag_import_export/default_profiles')->get('type/' . $request->profileEntity->getType()),
                SnippetsHelper::getNamespace('backend/swag_import_export/log')->get('export/success')
            );

            $this->logger->logProcessing('false', $request->filePath, $request->profileEntity->getName(), $message, 'true', $session);
        } catch (\Exception $e) {
            $this->logger->logProcessing('true', $request->filePath, $request->profileEntity->getName(), $e->getMessage(), 'false', $session);

            throw $e;
        }
    }
}
