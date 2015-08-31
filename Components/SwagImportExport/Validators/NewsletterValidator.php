<?php

namespace Shopware\Components\SwagImportExport\Validators;

use Shopware\Components\SwagImportExport\Utils\SnippetsHelper;
use Shopware\Components\SwagImportExport\Exception\AdapterException;

class NewsletterValidator
{
    private $requiredFields = array(
        'email',
    );

    private $snippetData = array(
        'email' => array(
            'adapters/newsletter/email_required',
            'Email address is required field.'
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