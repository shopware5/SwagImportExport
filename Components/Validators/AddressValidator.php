<?php
declare(strict_types=1);
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
    /**
     * @var array<string, array<string>>
     */
    protected array $requiredFields = [
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
     * @param array<string, mixed> $addressRecord
     */
    public function checkRequiredFields(array $addressRecord, bool $updateAddress = false): void
    {
        $this->validateEmptyAddressRecord($addressRecord);
        $this->validateCustomerCanBeIdentified($addressRecord);

        if ($updateAddress) {
            return;
        }

        $this->validateAddressFields($addressRecord);
    }

    /**
     * @param array<string, mixed> $addressRecord
     */
    private function validateEmptyAddressRecord(array $addressRecord): void
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
     * @param array<string, mixed> $addressRecord
     */
    private function validateAddressFields(array $addressRecord): void
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
     * @param array<string, mixed> $addressRecord
     */
    private function validateCustomerCanBeIdentified(array $addressRecord): void
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
     * @param array<string, mixed> $addressRecord
     */
    private function recordToString(array $addressRecord): string
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

    private function removeLastComma(string $message): string
    {
        return \substr($message, 0, -2);
    }
}
