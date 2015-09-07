<?php

namespace Shopware\Components\SwagImportExport\Validators;

use Shopware\Components\SwagImportExport\Utils\SnippetsHelper;
use Shopware\Components\SwagImportExport\Exception\AdapterException;

class CategoryValidator extends Validator
{
    public static $mapper = array(
        'int' => array(
            'categoryId',
            'parentId',
            'position',
            'active',
            'blog',
            'showFilterGroups',
            'hideFilter',
        ),
        'string' => array( //TODO: maybe we don't need to check fields which contains string?
            'name',
            'metaKeywords',
            'metaDescription',
            'cmsHeadline',
            'cmsText',
            'template',
            'external',
            'attributeAttribute1',
            'attributeAttribute2',
            'attributeAttribute3',
            'attributeAttribute4',
            'attributeAttribute5',
            'attributeAttribute6',
        ),
    );

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

    /**
     * Checks whether required fields are filled-in
     *
     * @param array $record
     * @throws AdapterException
     */
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