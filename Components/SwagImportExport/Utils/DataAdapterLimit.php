<?php

namespace Shopware\Components\SwagImportExport\Utils;

class DataLimit
{
    
    protected $limit = 50000;
    
    protected $offset = 0;

    public function getLimit()
    {
        return $this->limit;
    }

    public function getOffset()
    {
        return $this->offset;
    }

    public function setLimit($limit)
    {
        $this->limit = $limit;
    }

    public function setOffset($offset)
    {
        $this->offset = $offset;
    }

}
