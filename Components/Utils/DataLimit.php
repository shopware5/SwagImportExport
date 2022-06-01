<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport\Components\Utils;

class DataLimit
{
    protected ?int $limit = null;

    protected ?int $offset = null;

    public function __construct(array $options)
    {
        if (isset($options['limit'])) {
            $this->limit = $options['limit'];
        }

        if (isset($options['offset'])) {
            $this->offset = $options['offset'];
        }
    }

    public function getLimit(): ?int
    {
        return $this->limit;
    }

    public function getOffset(): ?int
    {
        return $this->offset;
    }
}
