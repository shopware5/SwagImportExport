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

class ArticleInStockValidator extends Validator
{
    public static array $mapper = [
        'string' => [ //TODO: maybe we don't need to check fields which contains string?
            'orderNumber',
            'additionalText',
            'supplier',
        ],
        'int' => ['inStock'],
        'float' => ['price'],
    ];

    private array $requiredFields = [
        'orderNumber',
    ];

    private array $snippetData = [
        'orderNumber' => [
            'adapters/ordernumber_required',
            'Order number is required',
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

            list($snippetName, $snippetMessage) = $this->snippetData[$key];

            $message = SnippetsHelper::getNamespace()->get($snippetName, $snippetMessage);
            throw new AdapterException($message);
        }
    }
}
