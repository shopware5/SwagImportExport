<?php

namespace Shopware\Components\SwagImportExport\Utils;

class DataLimit
{
    
    protected $limit;
    
    protected $offset;
    
    public function __construct(array $options)
    {
        if (isset($options['limit'])) {
            $this->limit = $options['limit'];
        } else {
            $this->limit = 0;
        }
        if (isset($options['offset'])) {
            $this->offset = $options['offset'];
        } else {
            $this->offset = 0;
        }
    }

    public function getLimit()
    {
        return $this->limit;
    }

    public function getOffset()
    {
        return $this->offset;
    }

}
