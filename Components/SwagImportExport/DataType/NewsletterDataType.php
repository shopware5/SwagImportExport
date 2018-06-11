<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Shopware\Components\SwagImportExport\DataType;

class NewsletterDataType
{
    /**
     * @var array
     */
    public static $mapper = [
        'email' => [
            'email',
        ],
        'string' => [
            'groupName',
            'salutation',
            'firstName',
            'lastName',
            'street',
            'streetNumber',
            'zipCode',
            'city',
        ],
        'int' => [
            'lastNewsletter',
            'lastRead',
            'userID',
        ],
    ];

    /**
     * @var array
     */
    public static $defaultFieldsForCreate = [
        'string' => [
            'groupName',
        ],
    ];
}
