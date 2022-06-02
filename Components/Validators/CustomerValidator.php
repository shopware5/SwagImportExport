<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport\Components\Validators;

use SwagImportExport\Components\Exception\AdapterException;
use SwagImportExport\Components\Utils\SnippetsHelper;

class CustomerValidator extends Validator
{
    /**
     * @var array<string>
     */
    private array $requiredFields = [
        'email',
    ];

    /**
     * @var array<string>
     */
    private array $requiredFieldsForCreate = [
        'unhashedPassword',
        'encoder',
        'customergroup',
        'billingSalutation',
        'billingFirstname',
        'billingLastname',
        'billingStreet',
        'billingZipcode',
        'billingCity',
        'billingCountryID',
    ];

    /**
     * @var array<string, array<string>>
     */
    private array $snippetData = [
        'email' => [
            'adapters/customer/email_required',
            'User email is required field.',
        ],
        'unhashedPassword' => [
            'adapters/customer/unhashedPassword_required',
            'Unhashed password must be provided for email %s.',
        ],
        'encoder' => [
            'adapters/customer/encoder_required',
            'To create a new user with email: %s, unhashedPassword must be provided and password must be empty.',
        ],
        'customergroup' => [
            'adapters/customer/customergroup_required',
            'Customer group must be provided for user with email: %s.',
        ],
        'billingSalutation' => [
            'adapters/customer/billingSalutation_required',
            'Billing salutation must be provided for user with email: %s.',
        ],
        'billingFirstname' => [
            'adapters/customer/billingFirstname_required',
            'Billing first name must be provided for user with email: %s.',
        ],
        'billingLastname' => [
            'adapters/customer/billingLastname_required',
            'Billing last name must be provided for user with email: %s.',
        ],
        'billingStreet' => [
            'adapters/customer/billingStreet_required',
            'Billing street must be provided for user with email: %s.',
        ],
        'billingZipcode' => [
            'adapters/customer/billingZipcode_required',
            'Billing zip code must be provided for user with email: %s.',
        ],
        'billingCity' => [
            'adapters/customer/billingCity_required',
            'Billing city must be provided for user with email: %s.',
        ],
        'billingCountryID' => [
            'adapters/customer/billingCountryID_required',
            'Billing countryId must be provided for user with email: %s.',
        ],
    ];

    /**
     * Checks whether required fields are filled-in
     *
     * @throws AdapterException
     */
    public function checkRequiredFields(array $record): void
    {
        foreach ($this->requiredFields as $key) {
            if (isset($record[$key])) {
                continue;
            }

            [$snippetName, $snippetMessage] = $this->snippetData[$key];

            $message = SnippetsHelper::getNamespace()->get($snippetName, $snippetMessage);
            throw new AdapterException(\sprintf($message, $record['email']));
        }
    }

    /**
     * Checks whether required fields for create are filled-in
     *
     * @throws AdapterException
     */
    public function checkRequiredFieldsForCreate(array $record): void
    {
        foreach ($this->requiredFieldsForCreate as $columnName) {
            if (isset($record[$columnName])) {
                continue;
            }

            switch ($columnName) {
                case 'unhashedPassword':
                    if (isset($record['password']) && isset($record['encoder'])) {
                        continue 2;
                    }
                    break;
            }

            [$snippetName, $snippetMessage] = $this->snippetData[$columnName];

            $message = SnippetsHelper::getNamespace()->get($snippetName, $snippetMessage);
            throw new AdapterException(\sprintf($message, $record['email']));
        }
    }
}
