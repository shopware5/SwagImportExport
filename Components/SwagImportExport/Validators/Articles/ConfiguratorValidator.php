<?php

namespace Shopware\Components\SwagImportExport\Validators\Articles;

use Shopware\Components\SwagImportExport\Validators\Validator;

class ConfiguratorValidator extends Validator
{
    public static $mapper = array(
        'int' => array(
            'configSetId',
            'configSetType',
        ),
        'string' => array( //TODO: maybe we don't need to check fields which contains string?
            'configGroupName',
            'configOptionName',
        ),
    );

    public function checkRequiredFields($record)
    {
    }
}
