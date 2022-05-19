<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport\Components\DataType;

class NewsletterDataType
{
    public static array $mapper = [
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

    public static array $defaultFieldsForCreate = [
        'string' => [
            'groupName',
        ],
    ];

    private function __construct()
    {
    }
}
