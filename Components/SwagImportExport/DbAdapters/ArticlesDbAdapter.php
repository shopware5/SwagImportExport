<?php

namespace Shopware\Components\SwagImportExport\DbAdapters;

class ArticlesDbAdapter extends DataDbAdapter
{
    private $repository;


    public function getRepository()
    {
        if($this->repository === null)
        {
            $this->repository = $this->getManager()->Article();
        }
        
        return $this->repository;
    }
}
