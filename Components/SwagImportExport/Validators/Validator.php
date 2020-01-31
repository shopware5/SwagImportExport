<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Shopware\Components\SwagImportExport\Validators;

use Shopware\Components\SwagImportExport\Exception\AdapterException;
use Shopware\Components\SwagImportExport\Utils\SnippetsHelper;

abstract class Validator
{
    /**
     * Removes fields which contain empty string.
     *
     * @param array $record
     *
     * @return array
     */
    public function filterEmptyString($record)
    {
        return array_filter($record, 'strlen');
    }

    /**
     * Validates fields types.
     *
     * @param array $record
     * @param array $mapper
     *
     * @throws AdapterException
     * @throws \Exception
     */
    public function validate($record, $mapper)
    {
        foreach ($record as $fieldName => $value) {
            foreach ($mapper as $type => $fields) {
                if (in_array($fieldName, $fields)) {
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
        return preg_match('/^-{0,1}\d+$/', $value);
    }

    /**
     * Validates fields with float type.
     *
     * @param string $value
     *
     * @return int
     */
    public function validateFloat($value)
    {
        return preg_match('/^-?\d+((\.|,){0,1}\d+)*$/', $value);
    }

    /**
     * Validates fields which contains date data.
     *
     * @return bool
     */
    public function validateDateTime($value)
    {
        return (bool) strtotime($value);
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
        return is_string($value);
    }

    /**
     * Helper function, which is used to validate current field's value.
     *
     * @param string $type
     * @param string $fieldName
     *
     * @throws AdapterException
     * @throws \Exception
     */
    private function validateType($type, $value, $fieldName)
    {
        $action = 'validate' . ucfirst($type);
        if (!is_callable([$this, $action])) {
            throw new \Exception('Method with name `' . $action . '` does not exist!');
        }

        $isCorrect = $this->$action($value);

        if (!$isCorrect) {
            $message = SnippetsHelper::getNamespace()->get('validators/wrong_type', '%s field has to be %s but is %s!');
            throw new AdapterException(sprintf($message, $fieldName, $type, $value));
        }
    }
}
