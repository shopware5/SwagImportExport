<?php

namespace Shopware\Components\SwagImportExport\DataType;

class NewsletterDataType
{
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

    public static $defaultFieldsForCreate = array(
        'string' => array(
            'groupName'
        )
    );
}