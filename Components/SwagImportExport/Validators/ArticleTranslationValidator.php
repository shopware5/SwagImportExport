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

class ArticleTranslationValidator extends Validator
{
    /**
     * @var array
     */
    public static $mapper = [
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
     * @var array
     */
    private $requiredFields = [
        'articleNumber' => 'adapters/ordernumber_required',
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
