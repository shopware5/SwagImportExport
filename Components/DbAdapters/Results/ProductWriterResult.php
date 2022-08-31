<?php
declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport\Components\DbAdapters\Results;

class ProductWriterResult
{
    private int $productId;

    private int $mainDetailId;

    private int $detailId;

    public function __construct(int $productId, int $mainDetailId, int $detailId)
    {
        $this->productId = $productId;
        $this->mainDetailId = $mainDetailId;
        $this->detailId = $detailId;
    }

    public function getProductId(): int
    {
        return $this->productId;
    }

    public function getMainDetailId(): int
    {
        return $this->mainDetailId;
    }

    public function getDetailId(): int
    {
        return $this->detailId;
    }
}
