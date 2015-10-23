<?php

namespace Shopware\Components\SwagImportExport\Validators;

use Shopware\Components\SwagImportExport\Utils\SnippetsHelper;
use Shopware\Components\SwagImportExport\Exception\AdapterException;

class CustomerValidator extends Validator
{
    private $requiredFields = array(
        'email'
    );

    private $requiredFieldsForCreate = array(
        'unhashedPassword',
        'encoder',
        'customergroup',
        'billingSalutation',
        'billingFirstname',
        'billingLastname',
        'billingStreet',
        'billingZipcode',
        'billingCity',
        'billingCountryID'
    );

    private $snippetData = array(
        'email' => array(
            'adapters/customer/email_required',
            'User email is required field.'
        ),
        'unhashedPassword' => array(
            'adapters/customer/unhashedPassword_required',
            'Unhashed password must be provided for email %s.'
        ),
        'encoder' => array(
            'adapters/customer/encoder_required',
            'To create a new user with email: %s, unhashedPassword must be provided and password must be empty.'
        ),
        'customergroup' => array(
            'adapters/customer/customergroup_required',
            'Customer group must be provided for user with email: %s.'
        ),
        'billingSalutation' => array(
            'adapters/customer/billingSalutation_required',
            'Billing salutation must be provided for user with email: %s.'
        ),
        'billingFirstname' => array(
            'adapters/customer/billingFirstname_required',
            'Billing first name must be provided for user with email: %s.'
        ),
        'billingLastname' => array(
            'adapters/customer/billingLastname_required',
            'Billing last name must be provided for user with email: %s.'
        ),
        'billingStreet' => array(
            'adapters/customer/billingStreet_required',
            'Billing street must be provided for user with email: %s.'
        ),
        'billingZipcode' => array(
            'adapters/customer/billingZipcode_required',
            'Billing zip code must be provided for user with email: %s.'
        ),
        'billingCity' => array(
            'adapters/customer/billingCity_required',
            'Billing city must be provided for user with email: %s.'
        ),
        'billingCountryID' => array(
            'adapters/customer/billingCountryID_required',
            'Billing countryId must be provided for user with email: %s.'
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

    /**
     * Checks whether required fields for create are filled-in
     *
     * @param array $record
     * @throws AdapterException
     */
    public function checkRequiredFieldsForCreate($record)
    {
        foreach ($this->requiredFieldsForCreate as $key) {
            if (isset($record[$key])) {
                continue;
            }

            switch ($key) {
                case 'unhashedPassword':
                    if ((isset($record['password']) && isset($record['encoder']))) {
                        continue 2;
                    }
                    break;
            }

            list($snippetName, $snippetMessage) = $this->snippetData[$key];

            $message = SnippetsHelper::getNamespace()->get($snippetName, $snippetMessage);
            throw new AdapterException(sprintf($message, $record['email']));
        }
    }
}