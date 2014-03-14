<?php

namespace Shopware\Components\SwagImportExport\DataAdapters;

class ArticlesAdapter extends DataAdapter
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
