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

class TranslationValidator extends Validator
{
    /**
     * @var array<string, array<string>>
     */
    public static array $mapper = [
        'string' => ['name', 'baseName', 'objectType', 'description'],
        'int' => ['objectKey', 'languageId'],
    ];

    /**
     * Indexed by field name
     * Value: snippet name
     */
    protected array $requiredFields = [
        'objectType' => 'adapters/translations/object_type_not_found',
        'objectKey' => 'adapters/translations/object_key_not_found',
        'name' => 'adapters/translations/element_name_not_found',
        'languageId' => 'adapters/translations/language_not_found',
    ];

    /**
     * Checks whether required fields are filled-in
     *
     * @param array<string, mixed> $record
     */
    public function checkRequiredFields(array $record): void
    {
        foreach ($this->requiredFields as $field => $snippet) {
            if (isset($record[$field])) {
                continue;
            }

            $message = SnippetsHelper::getNamespace()->get($snippet);
            throw new AdapterException($message);
        }
    }
}
