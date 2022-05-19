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

class ArticleImageValidator extends Validator
{
    public static $mapper = [
        'string' => [
            'ordernumber',
            'image',
            'description',
            'relations',
        ],
        'int' => [
            'main',
            'position',
            'width',
            'height',
        ],
    ];

    private $requiredFields = [
        'ordernumber',
        'image',
    ];

    private $snippetData = [
        'ordernumber' => [
            'adapters/articlesImages/ordernumber_image_required',
            'Ordernumber and image are required',
        ],
        'image' => [
            'adapters/articlesImages/ordernumber_image_required',
            'Ordernumber and image are required',
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
