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

class CategoryValidator extends Validator
{
    /**
     * @var array<string>
     */
    protected array $requiredFields = [
        'name',
        'parentId',
        'categoryId',
    ];

    /**
     * @var array<string, array<string>>
     */
    protected array $snippetData = [
        'name' => [
            'adapters/categories/name_required',
            'Category name is required',
        ],
        'parentId' => [
            'adapters/categories/parent_id_required',
            'Parent category id is required for category %s',
            'name',
        ],
        'categoryId' => [
            'adpaters/categories/id_required',
            'Category id is required. If you don\'t import an id, child- and father categories could not get referenced to each other.',
            'id',
        ],
    ];

    /**
     * Checks whether required fields are filled-in
     *
     * @throws AdapterException
     */
    public function checkRequiredFields(array $record): void
    {
        foreach ($this->requiredFields as $key) {
            if (isset($record[$key])) {
                continue;
            }

            [$snippetName, $snippetMessage, $messageKey] = $this->snippetData[$key];

            $message = SnippetsHelper::getNamespace()->get($snippetName, $snippetMessage);
            throw new AdapterException(\sprintf($message, $record[$messageKey]));
        }
    }
}
