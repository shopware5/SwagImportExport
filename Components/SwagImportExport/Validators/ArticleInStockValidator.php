<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Shopware\Components\SwagImportExport\Validators;

use Shopware\Components\SwagImportExport\Utils\SnippetsHelper;
use Shopware\Components\SwagImportExport\Exception\AdapterException;

class ArticleInStockValidator extends Validator
{
    public static $mapper = array(
        'string' => array( //TODO: maybe we don't need to check fields which contains string?
            'orderNumber',
            'additionalText',
            'supplier',
        ),
        'int' => array('inStock'),
        'float' => array('price'),
    );

    private $requiredFields = array(
        'orderNumber',
    );

    private $snippetData = array(
        'orderNumber' => array(
            'adapters/ordernumber_required',
            'Order number is required'
        ),
    );

    /**
     * Checks whether required fields are filled-in
     *
     * @param array $record
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
