<?php

declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport\Components\Structs;

use SwagImportExport\Components\DbAdapters\DataDbAdapter;
use SwagImportExport\Components\Profile\Profile;

class ExportRequest
{
    private const CUSTOM_STOCK_FILTER = 'custom';

    public string $format;

    public Profile $profileEntity;

    public string $filePath;

    public ?int $limit = null;

    public ?int $offset = null;

    /**
     * @var array<string, mixed>
     */
    public array $filter = [];

    public string $username = 'Cli';

    public ?int $sessionId = null;

    /**
     * @var array<string>|null
     */
    public ?array $columnOptions = null;

    public int $batchSize = 1000;

    /**
     * @param array<string, mixed> $data
     */
    public function setData(array $data): void
    {
        foreach ($data as $key => $value) {
            if (!property_exists($this, $key)) {
                continue;
            }

            $this->{$key} = $value;
        }

        if ($data['exportVariants']) {
            $this->filter['variants'] = $data['exportVariants'];
        }

        if ($data['category']) {
            $this->filter['categories'] = $data['category'];
        }

        if ($data['productStream']) {
            $this->filter['productStreamId'] = $data['productStream'];
        }

        if ($data['customerStream']) {
            $this->filter['customerStreamId'] = $data['customerStream'];
        }

        if ($data['dateFrom']) {
            $this->filter['dateFrom'] = $data['dateFrom'];
        }

        if ($data['dateTo']) {
            $this->filter['dateTo'] = $data['dateTo'];
        }

        if ($this->profileEntity->getType() === DataDbAdapter::PRODUCT_INSTOCK_ADAPTER) {
            if ($data['stockFilter']) {
                $this->filter['stockFilter'] = $data['stockFilter'];
            }

            if ($data['stockFilter'] === self::CUSTOM_STOCK_FILTER) {
                $this->filter['direction'] = $data['customFilterDirection'];
                $this->filter['value'] = $data['customFilterValue'];
            }
        }

        if (\in_array($this->profileEntity->getType(), [DataDbAdapter::ORDER_ADAPTER, DataDbAdapter::MAIN_ORDER_ADAPTER], true)) {
            if ($data['ordernumberFrom']) {
                $this->filter['ordernumberFrom'] = $data['ordernumberFrom'];
            }

            if ($data['dateFrom']) {
                $this->filter['dateFrom'] = new \DateTime($data['dateFrom']);
            }

            if ($data['dateTo']) {
                $dateTo = new \DateTime($data['dateTo']);
                $dateTo->setTime(23, 59, 59);
                $this->filter['dateTo'] = $dateTo;
            }

            if ($data['orderstate']) {
                $this->filter['orderstate'] = $data['orderstate'];
            }

            if ($data['paymentstate']) {
                $this->filter['paymentstate'] = $data['paymentstate'];
            }
        }

        // customer stream filter for addresses and customers
        if (\in_array($this->profileEntity->getType(), [DataDbAdapter::CUSTOMER_ADAPTER, DataDbAdapter::ADDRESS_ADAPTER], true)) {
            if ($data['customerStreamId']) {
                $this->filter['customerStreamId'] = $data['customerStreamId'];
            }
        }

        if ($this->profileEntity->getType() === DataDbAdapter::CUSTOMER_COMPLETE_ADAPTER && $data['customerId']) {
            $this->filter['customerId'] = $data['customerId'];
        }
    }
}
