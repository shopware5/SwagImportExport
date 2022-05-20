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

class CategoryTranslationValidator extends Validator
{
    /**
     * @var array<string>
     */
    private array $requiredFields = [
        'categoryId' => 'adapters/category_required',
        'languageId' => 'adapters/translations/language_not_found',
    ];

    /**
     * Checks whether required fields are filled-in
     *
     * @param array $record
     *
     * @throws AdapterException
     */
    public function checkRequiredFields($record)
    {
        foreach ($this->requiredFields as $requiredField => $snippetName) {
            if (isset($record[$requiredField])) {
                continue;
            }

            $message = SnippetsHelper::getNamespace()->get($snippetName);

            throw new AdapterException($message);
        }
    }
}
