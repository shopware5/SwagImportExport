<?php

namespace Shopware\Components\SwagImportExport\DbAdapters\Articles;

use Shopware\Components\SwagImportExport\DbalHelper;

class ConfiguratorWriter
{
    public function __construct()
    {
        $this->dbalHelper = new DbalHelper();
    }

    public function write($articleId, $articleDetailId, $configuratorData)
    {

        foreach ($configuratorData as $configurator) {
            if ($this->isValid($configurator)) {
                continue;
            }
        }



    }

    private function isValid($configurator)
    {
        if (!$configurator['configGroupName'] && !$configurator['configGroupId']) {
            return false;
        }

        if (!$configurator['configOptionName'] && !$configurator['configOptionId']) {
            return false;
        }

        return true;
    }
}