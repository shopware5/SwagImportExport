<?php

namespace Shopware\Components\SwagImportExport\Validators;

use Shopware\Components\SwagImportExport\Utils\SnippetsHelper;
use Shopware\Components\SwagImportExport\Exception\AdapterException;

class ArticleTranslationValidator extends Validator
{
    public static $mapper = array(
        'string' => array( //TODO: maybe we don't need to check fields which contains string?
            'articleNumber',
            'name',
            'description',
            'descriptionLong',
            'keywords',
            'metaTitle'
        ),
        'int' => array('languageId'),
    );

    private $requiredFields = array(
        'articleNumber',
    );

    private $snippetData = array(
        'articleNumber' => array(
            'adapters/ordernumber_required',
            'Order number is required.'
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

            list($snippetName, $snippetMessage) = $this->snippetData[$key];

            $message = SnippetsHelper::getNamespace()->get($snippetName, $snippetMessage);
            throw new AdapterException($message);
        }
    }
}
