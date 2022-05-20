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

class AddressValidator extends Validator
{
    private array $requiredFields = [
        'firstname' => [
            'snippet' => 'adapters/address/firstname_required',
            'default' => 'Field firstname is required. Record %s',
        ],
        'lastname' => [
            'snippet' => 'adapters/address/lastname_required',
            'default' => 'Field lastname is required. Record %s',
        ],
        'zipcode' => [
            'snippet' => 'adapters/address/zipcode_required',
            'default' => 'Field zipcode is required. Record %s',
        ],
        'city' => [
            'snippet' => 'adapters/address/city_required',
            'default' => 'Field city is required. Record %s',
        ],
        'countryID' => [
            'snippet' => 'adapters/address/country_id_required',
            'default' => 'Field countryID is required. Record %s',
        ],
    ];

    /**
     * @param array $addressRecord
     * @param bool  $updateAddress
     */
    public function checkRequiredFields($addressRecord, $updateAddress = false)
    {
        $this->validateEmptyAddressRecord($addressRecord);
        $this->validateCustomerCanBeIdentified($addressRecord);

        if ($updateAddress) {
            return;
        }

        $this->validateAddressFields($addressRecord);
    }

    /**
     * @param array $addressRecord
     *
     * @throws AdapterException
     */
    private function validateEmptyAddressRecord($addressRecord)
    {
        if (\count($addressRecord) === 0) {
            throw new AdapterException(
                SnippetsHelper::getNamespace()->get(
                    'adapters/address/no_records',
                    'Could not find address records.'
                )
            );
        }
    }

    /**
     * @param array $addressRecord
     *
     * @throws AdapterException
     */
    private function validateAddressFields($addressRecord)
    {
        foreach ($this->requiredFields as $field => $snippetData) {
            if ($addressRecord[$field] !== '') {
                continue;
            }

            $message = SnippetsHelper::getNamespace()->get($snippetData['snippet'], $snippetData['default']);
            $recordDataForMessage = $this->recordToString($addressRecord);
            throw new AdapterException(\sprintf($message, $recordDataForMessage));
        }
    }

    /**
     * @param array $addressRecord
     *
     * @throws AdapterException
     */
    private function validateCustomerCanBeIdentified($addressRecord)
    {
        if (
            !($addressRecord['customernumber'] && $addressRecord['email'])
            && !$addressRecord['userID']
        ) {
            $message = SnippetsHelper::getNamespace()->get('adapters/address/cant_identify_customer');
            $recordDataForMessage = $this->recordToString($addressRecord);
            throw new AdapterException(\sprintf($message, $recordDataForMessage));
        }
    }

    /**
     * @param array $addressRecord
     *
     * @return string
     */
    private function recordToString($addressRecord)
    {
        $messageTemplate = '%s: %s, ';
        $message = '';
        foreach ($addressRecord as $fieldName => $value) {
            if (!$value) {
                $value = ' - ';
            }
            $message .= \sprintf($messageTemplate, $fieldName, $value);
        }

        return $this->removeLastComma($message);
    }

    /**
     * @param string $message
     *
     * @return string
     */
    private function removeLastComma($message)
    {
        return \substr($message, 0, -2);
    }
}
