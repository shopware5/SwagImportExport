<?php
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

class PriceValidator extends Validator
{
    public static $mapper = [
        'float' => [
            'price',
            'purchasePrice',
            'pseudoPrice',
            'regulationPrice',
        ],
    ];

    private $requiredFields = [
        ['price', 'priceGroup'],
    ];

    private $snippetData = [
        'price' => [
            'adapters/articles/incorrect_price',
            'Price value is incorrect for article with number %s',
        ],
    ];

    /**
     * Checks whether required fields are filled-in
     *
     * @param array  $record
     * @param string $orderNumber
     *
     * @throws AdapterException
     */
    public function checkRequiredFields($record, $orderNumber)
    {
        foreach ($this->requiredFields as $key) {
            list($price, $priceGroup) = $key;
            if (!empty($record[$price]) || $record[$priceGroup] !== 'EK') {
                continue;
            }

            $key = $price;

            list($snippetName, $snippetMessage) = $this->snippetData[$key];

            $message = SnippetsHelper::getNamespace()->get($snippetName, $snippetMessage);
            throw new AdapterException(\sprintf($message, $orderNumber));
        }
    }
}
