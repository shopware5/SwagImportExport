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

class ArticleImageValidator extends Validator
{
    public static $mapper = array(
        'string' => array( //TODO: maybe we don't need to check fields which contains string?
            'ordernumber',
            'image',
            'description',
            'relations',
        ),
        'int' => array(
            'main',
            'position',
            'width',
            'height',
        ),
    );

    private $requiredFields = array(
        'ordernumber',
        'image',
    );

    private $snippetData = array(
        'ordernumber' => array(
            'adapters/articlesImages/ordernumber_image_required',
            'Ordernumber and image are required'
        ),
        'image' => array(
            'adapters/articlesImages/ordernumber_image_required',
            'Ordernumber and image are required'
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
