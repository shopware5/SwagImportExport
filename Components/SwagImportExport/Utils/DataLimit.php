<?php

namespace Shopware\Components\SwagImportExport\Utils;

class DataLimit
{
    protected $limit;

    protected $offset;

    /**
     * @param array $options
     */
    public function __construct(array $options)
    {
        if (isset($options['limit'])) {
            $this->limit = $options['limit'];
        }

        if (isset($options['offset'])) {
            $this->offset = $options['offset'];
        }
    }

    /**
     * @return mixed
     */
    public function getLimit()
    {
        return $this->limit;
    }

    /**
     * @return mixed
     */
    public function getOffset()
    {
        return $this->offset;
    }
}
