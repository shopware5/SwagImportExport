<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport\Components\DbAdapters\Results;

class ArticleWriterResult
{
    private int $articleId;

    private int $mainDetailId;

    private int $detailId;

    public function __construct(int $articleId, int $mainDetailId, int $detailId)
    {
        $this->articleId = $articleId;
        $this->mainDetailId = $mainDetailId;
        $this->detailId = $detailId;
    }

    public function getArticleId(): int
    {
        return $this->articleId;
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
