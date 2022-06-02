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

class ArticleTranslationValidator extends Validator
{
    /**
     * @var array<string, array<string>>
     */
    public static array $mapper = [
        'string' => [ // TODO: maybe we don't need to check fields which contains string?
            'articleNumber',
            'name',
            'description',
            'descriptionLong',
            'keywords',
            'metaTitle',
        ],
        'int' => ['languageId'],
    ];

    /**
     * @var array<string>
     */
    protected array $requiredFields = [
        'articleNumber' => 'adapters/ordernumber_required',
        'languageId' => 'adapters/translations/language_not_found',
    ];

    /**
     * Checks whether required fields are filled-in
     *
     * @param array<string, mixed> $record
     */
    public function checkRequiredFields(array $record): void
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
