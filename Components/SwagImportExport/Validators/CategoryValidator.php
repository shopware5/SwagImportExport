<?php

namespace Shopware\Components\SwagImportExport\Validators;

use Shopware\Components\SwagImportExport\Utils\SnippetsHelper;
use Shopware\Components\SwagImportExport\Exception\AdapterException;

class CategoryValidator extends Validator
{
    private $requiredFields = array(
        'name',
        'parentId',
    );

    private $snippetData = array(
        'name' => array(
            'adapters/categories/name_required',
            'Category name is required'
        ),
        'parentId' => array(
            'adapters/categories/parent_id_required',
            'Parent category id is required for category %s',
            'name'
        ),
    );

    public function checkRequiredFields($record)
    {
        foreach ($this->requiredFields as $key) {
            if (isset($record[$key])) {
                continue;
            }

            list($snippetName, $snippetMessage, $messageKey) = $this->snippetData[$key];

            $message = SnippetsHelper::getNamespace()->get($snippetName, $snippetMessage);
            throw new AdapterException(sprintf($message, $record[$messageKey]));
        }
    }
}