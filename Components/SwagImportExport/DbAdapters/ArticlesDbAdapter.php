<?php

namespace Shopware\Components\SwagImportExport\DbAdapters;

class ArticlesDbAdapter implements DataDbAdapter
{
    private $repository;
    
    public function read($ids, $columns)
    {
        
    }

    public function getRepository()
    {
        if($this->repository === null)
        {
            $this->repository = $this->getManager()->Article();
        }
        
        return $this->repository;
    }
}
