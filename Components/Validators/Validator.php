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

abstract class Validator
{
    /**
     * Removes fields which contain empty string.
     */
    public function filterEmptyString(array $record): array
    {
        return \array_filter($record, 'strlen');
    }

    /**
     * Validates fields types.
     *
     * @throws AdapterException
     * @throws \Exception
     */
    public function validate(array $record, array $mapper): void
    {
        foreach ($record as $fieldName => $value) {
            foreach ($mapper as $type => $fields) {
                if (\in_array($fieldName, $fields)) {
                    $this->validateType($type, (string) $value, $fieldName);
                    break;
                }
            }
        }
    }

    /**
     * Validates fields with int type. It is possible this field to have as a value '-1'.
     */
    public function validateInt(string $value): bool
    {
        return (bool) \preg_match('/^-{0,1}\d+$/', $value);
    }

    /**
     * Validates fields with float type.
     */
    public function validateFloat(string $value): bool
    {
        return (bool) \preg_match('/^-?\d+((\.|,){0,1}\d+)*$/', $value);
    }

    /**
     * Validates fields which contains date data.
     */
    public function validateDateTime(string $value): bool
    {
        return (bool) \strtotime($value);
    }

    /**
     * Validates email fields.
     *
     * @throws \Exception
     */
    public function validateEmail(string $email): bool
    {
        return (bool) preg_match('/^\S+\@\S+\.\S+$/', $email);
    }

    /**
     * Validates fields which contains string.
     */
    public function validateString(string $value): bool
    {
        return true;
    }

    /**
     * Checks whether required fields are filled-in
     *
     * @param array<string, mixed> $record
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
            throw new AdapterException($message);
        }
    }

    /**
     * Helper function, which is used to validate current field's value.
     */
    private function validateType(string $type, string $value, string $fieldName): void
    {
        $action = 'validate' . \ucfirst($type);
        if (!\is_callable([$this, $action])) {
            throw new \Exception('Method with name `' . $action . '` does not exist!');
        }

        $isCorrect = $this->$action($value);

        if (!$isCorrect) {
            $message = SnippetsHelper::getNamespace()->get('validators/wrong_type', '%s field has to be %s but is %s!');
            throw new AdapterException(\sprintf($message, $fieldName, $type, $value));
        }
    }
}
