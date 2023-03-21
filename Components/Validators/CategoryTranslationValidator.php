<?php

declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport\Components\Validators;

class CategoryTranslationValidator extends Validator
{
    /**
     * @var array<string, array<string>>
     */
    protected array $snippetData = [
        'categoryId' => [
            'adapters/category_translation/category_not_found',
            'Category id is required',
        ],
        'languageId' => [
            'adapters/translations/language_not_found',
            'Language id is required.',
        ],
    ];

    /**
     * @var array<string>
     */
    protected array $requiredFields = [
        'categoryId',
        'languageId',
    ];
}
