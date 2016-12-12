<?php

/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Shopware\Components\SwagImportExport\Service;

use Shopware\Components\SwagImportExport\DataWorkflow;
use Shopware\Components\SwagImportExport\DbAdapters\DataDbAdapter;
use Shopware\Components\SwagImportExport\Service\Struct\PreparationResultStruct;
use Shopware\Components\SwagImportExport\Utils\SnippetsHelper;

/**
 * @package Shopware\Components\SwagImportExport\Service
 */
class ExportService extends AbstractImportExportService implements ExportServiceInterface
{
    /**
     * @param array $requestData
     * @param array $filterParams
     */
    public function prepareExport(array $requestData, array $filterParams)
    {
        $serviceHelpers = $this->buildServiceHelpers($requestData);
        $requestData['filter'] = $this->prepareFilter($serviceHelpers->getProfile()->getType(), $filterParams);

        $this->initializeDataIO($serviceHelpers->getDataIO(), $requestData);

        $recordIds = $serviceHelpers->getDataIO()->preloadRecordIds()->getRecordIds();

        $position = $serviceHelpers->getDataIO()->getSessionPosition();
        $position = $position == null ? 0 : $position;

        return new PreparationResultStruct($position, count($recordIds));
    }

    /**
     * @param array $requestData
     * @param array $filterParams
     * @return array
     * @throws \Exception
     */
    public function export(array $requestData, array $filterParams)
    {
        $serviceHelpers = $this->buildServiceHelpers($requestData);
        $requestData['filter'] = $this->prepareFilter($serviceHelpers->getProfile()->getType(), $filterParams);

        $this->initializeDataIO($serviceHelpers->getDataIO(), $requestData);

        $dataTransformerChain = $this->createDataTransformerChain(
            $serviceHelpers->getProfile(),
            $serviceHelpers->getFileWriter()->hasTreeStructure()
        );

        $dataWorkflow = new DataWorkflow(
            $serviceHelpers->getDataIO(),
            $serviceHelpers->getProfile(),
            $dataTransformerChain,
            $serviceHelpers->getFileWriter()
        );

        $session = $serviceHelpers->getSession()->getEntity();

        try {
            $resultData = $dataWorkflow->export($requestData);
            $message = sprintf(
                '%s %s %s',
                $resultData['position'],
                SnippetsHelper::getNamespace('backend/swag_import_export/default_profiles')->get('type/' . $serviceHelpers->getProfile()->getType()),
                SnippetsHelper::getNamespace('backend/swag_import_export/log')->get('export/success')
            );

            $this->logProcessing('false', $resultData['fileName'], $serviceHelpers->getProfile()->getName(), $message, 'true', $session);
            unset($resultData['filter']);

            return $resultData;
        } catch (\Exception $e) {
            $this->logProcessing('true', $requestData['fileName'], $serviceHelpers->getProfile()->getName(),  $e->getMessage(), 'false', $session);

            throw $e;
        }
    }

    /**
     * @param string $profileType
     * @param array $filterParams
     * @return array
     */
    private function prepareFilter($profileType, $filterParams)
    {
        $filterParams = array_filter($filterParams, 'strlen');
        $filter = [];

        // prepare article filter
        if ($profileType === DataDbAdapter::ARTICLE_ADAPTER) {
            $filter['variants'] = $filterParams['variants'] ? true : false;
            if (isset($filterParams['categories'])) {
                $filter['categories'] = [
                    $filterParams['categories']
                ];
            }
        }

        // prepare articlesInStock filter
        if ($profileType === DataDbAdapter::ARTICLE_INSTOCK_ADAPTER && isset($filterParams['stockFilter'])) {
            $filter['stockFilter'] = $filterParams['stockFilter'];
            if ($filter['stockFilter'] === 'custom') {
                $filter['direction'] = $filterParams['customFilterDirection'];
                $filter['value'] = $filterParams['customFilterValue'];
            }
        }

        // prepare orders and mainOrders filter
        if (in_array($profileType, [DataDbAdapter::ORDER_ADAPTER, DataDbAdapter::MAIN_ORDER_ADAPTER], true)) {
            if (isset($filterParams['ordernumberFrom'])) {
                $filter['ordernumberFrom'] = $filterParams['ordernumberFrom'];
            }

            if (isset($filterParams['dateFrom'])) {
                $filter['dateFrom'] = new \DateTime($filterParams['dateFrom']);
            }

            if (isset($filterParams['dateTo'])) {
                $dateTo = new \DateTime($filterParams['dateTo']);
                $dateTo->setTime(23, 59, 59);
                $filter['dateTo'] = $dateTo;
            }

            if (isset($filterParams['orderstate'])) {
                $filter['orderstate'] = $filterParams['orderstate'];
            }

            if (isset($filterParams['paymentstate'])) {
                $filter['paymentstate'] = $filterParams['paymentstate'];
            }
        }

        return $filter;
    }
}