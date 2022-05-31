<?php
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
     * @var array<string>
     */
    protected array $requiredFields = [
        'categoryId' => 'adapters/category_required',
        'languageId' => 'adapters/translations/language_not_found',
    ];
}
