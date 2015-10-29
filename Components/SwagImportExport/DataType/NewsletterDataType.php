<?php

namespace Shopware\Components\SwagImportExport\DataType;

class NewsletterDataType
{
    /**
     * @var array
     */
    public static $mapper = array(
        'email' => array(
            'email'
        ),
        'string' => array(
            'groupName',
            'salutation',
            'firstName',
            'lastName',
            'street',
            'streetNumber',
            'zipCode',
            'city',
        ),
        'int' => array(
            'lastNewsletter',
            'lastRead',
            'userID'
        ),
    );

    /**
     * @var array
     */
    public static $defaultFieldsForCreate = array(
        'string' => array(
            'groupName'
        )
    );
}
