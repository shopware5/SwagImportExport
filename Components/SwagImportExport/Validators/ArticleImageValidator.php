<?php

namespace Shopware\Components\SwagImportExport\Validators;

use Shopware\Components\SwagImportExport\Utils\SnippetsHelper;
use Shopware\Components\SwagImportExport\Exception\AdapterException;

class ArticleImageValidator extends Validator
{
    private $requiredFields = array(
        'ordernumber',
        'image',
    );

    private $snippetData = array(
        'ordernumber' => array(
            'adapters/articlesImages/ordernumber_image_required',
            'Ordernumber and image are required'
        ),
        'image' => array(
            'adapters/articlesImages/ordernumber_image_required',
            'Ordernumber and image are required'
        ),
    );

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