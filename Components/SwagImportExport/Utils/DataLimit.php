<?php

namespace Shopware\Components\SwagImportExport\Utils;

class DataLimit
{
    
    protected $limit;
    
    protected $offset;
    
    public function __construct(array $options)
    {
        $this->limit = $options['limit'];
        $this->offset = $options['offset'];
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
