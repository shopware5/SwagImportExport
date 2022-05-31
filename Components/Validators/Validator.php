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

abstract class Validator
{
    /**
     * Removes fields which contain empty string.
     *
     * @return array
     */
    public function filterEmptyString(array $record)
    {
        return \array_filter($record, 'strlen');
    }

    /**
     * Validates fields types.
     *
     * @throws AdapterException
     * @throws \Exception
     */
    public function validate(array $record, array $mapper)
    {
        foreach ($record as $fieldName => $value) {
            foreach ($mapper as $type => $fields) {
                if (\in_array($fieldName, $fields)) {
                    $this->validateType($type, $value, $fieldName);
                    break;
                }
            }
        }
    }

    /**
     * Validates fields with int type. It is possible this field to has as a value '-1'.
     *
     * @return int
     */
    public function validateInt($value)
    {
        return \preg_match('/^-{0,1}\d+$/', $value);
    }

    /**
     * Validates fields with float type.
     *
     * @return int
     */
    public function validateFloat(string $value)
    {
        return \preg_match('/^-?\d+((\.|,){0,1}\d+)*$/', $value);
    }

    /**
     * Validates fields which contains date data.
     *
     * @return bool
     */
    public function validateDateTime($value)
    {
        return (bool) \strtotime($value);
    }

    /**
     * Validates email fields.
     *
     * @throws \Exception
     */
    public function validateEmail($email)
    {
        /** @var \Shopware\Components\Validator\EmailValidatorInterface $emailValidator */
        $emailValidator = Shopware()->Container()->get('validator.email');

        return $emailValidator->isValid($email);
    }

    /**
     * Validates fields which contains string.
     *
     * @return bool
     */
    public function validateString($value)
    {
        return \is_string($value);
    }

    /**
     * Checks whether required fields are filled-in
     *
     * @param array<string, mixed> $record
     *
     * @throws AdapterException
     */
    public function checkRequiredFields(array $record)
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
    private function validateType(string $type, $value, string $fieldName)
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
