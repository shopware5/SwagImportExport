<?php

namespace Shopware\Components\SwagImportExport\Profile;

class Profile
{

    private $type;
    private $nameMapping;
    private $conversions;
    private $treeTemplate;

    public function getType()
    {
        return $this->type;
    }

    public function getNameMapping()
    {
        return $this->nameMapping;
    }

    public function getConversions()
    {
        return $this->conversions;
    }

    public function getTreeTemplate()
    {
        return $this->treeTemplate;
    }

}
