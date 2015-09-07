<?php

namespace Shopware\Components\SwagImportExport\Validators;

use Shopware\Components\SwagImportExport\Utils\SnippetsHelper;
use Shopware\Components\SwagImportExport\Exception\AdapterException;

class NewsletterValidator extends Validator
{
    public static $mapper = array(
        'email' => array('email'),
        'string' => array( //TODO: maybe we don't need to check fields which contains string?
            'groupName',
            'salutation',
            'firstName',
            'lastName',
            'street',
            'streetNumber',
            'zipCode',
            'city',
        ),
        'int' => array('lastNewsletter', 'lastRead', 'userID'),
    );

    private $requiredFields = array(
        'email',
    );

    private $snippetData = array(
        'email' => array(
            'adapters/newsletter/email_required',
            'Email address is required field.'
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