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

class CategoryValidator extends Validator
{
    private $requiredFields = [
        'name',
        'parentId',
        'categoryId',
    ];

    private $snippetData = [
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
     * @param array $record
     *
     * @throws AdapterException
     */
    public function checkRequiredFields($record)
    {
        foreach ($this->requiredFields as $key) {
            if (isset($record[$key])) {
                continue;
            }

            list($snippetName, $snippetMessage, $messageKey) = $this->snippetData[$key];

            $message = SnippetsHelper::getNamespace()->get($snippetName, $snippetMessage);
            throw new AdapterException(sprintf($message, $record[$messageKey]));
        }
    }
}
