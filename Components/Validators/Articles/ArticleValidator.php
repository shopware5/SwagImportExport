<?php
declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport\Components\Validators\Articles;

use SwagImportExport\Components\Exception\AdapterException;
use SwagImportExport\Components\Utils\SnippetsHelper;
use SwagImportExport\Components\Validators\Validator;

class ArticleValidator extends Validator
{
    /**
     * @var array<string>
     */
    protected array $requiredFields = [
        'orderNumber',
        'mainNumber',
    ];

    /**
     * @var array<string|array<string>>
     */
    protected array $requiredFieldsForCreate = [
        'name',
        'mainNumber',
        ['supplierName', 'supplierId'],
        'taxId',
    ];

    /**
     * @var array<string, array<string>>
     */
    protected array $snippetData = [
        'orderNumber' => [
            'adapters/ordernumber_required',
            'Order number is required.',
        ],
        'mainNumber' => [
            'adapters/mainnumber_required',
            'Main number is required for article %s.',
        ],
        'supplierName' => [
            'adapters/articles/supplier_not_found',
            'Supplier not found for article %s.',
        ],
        'name' => [
            'adapters/articles/no_name_provided',
            'Please provide article name for article %s.',
        ],
        'taxId' => [
            'adapters/articles/no_tax_provided',
            'Tax not provided for article %s.',
        ],
    ];

    /**
     * Checks whether required fields are filled-in
     *
     * @param array<string, mixed> $record
     */
    public function checkRequiredFields(array $record): void
    {
        foreach ($this->requiredFields as $key) {
            if (isset($record[$key]) && \strlen($record[$key])) {
                continue;
            }

            [$snippetName, $snippetMessage] = $this->snippetData[$key];

            $message = SnippetsHelper::getNamespace()->get($snippetName, $snippetMessage);
            throw new AdapterException(\sprintf($message, $record['orderNumber']));
        }
    }

    /**
     * Checks whether required fields for create are filled-in
     *
     * @param array<string, mixed> $record
     */
    public function checkRequiredFieldsForCreate(array $record): void
    {
        foreach ($this->requiredFieldsForCreate as $key) {
            if (\is_array($key)) {
                [$supplierName, $supplierId] = $key;

                if (isset($record[$supplierName]) || isset($record[$supplierId])) {
                    continue;
                }
                $key = $supplierName;
            } elseif (isset($record[$key]) && !empty($record[$key])) {
                continue;
            }

            [$snippetName, $snippetMessage] = $this->snippetData[$key];

            $message = SnippetsHelper::getNamespace()->get($snippetName, $snippetMessage);
            throw new AdapterException(\sprintf($message, $record['mainNumber']));
        }
    }
}
