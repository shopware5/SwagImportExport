<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Shopware\Components\SwagImportExport\DataType;

class CategoryDataType
{
    /**
     * @var array
     */
    public static $mapper = array(
        'int' => array(
            'categoryId',
            'parentId',
            'position',
            'active',
            'blog',
            'hideFilter'
        ),
        'string' => array(
            'name',
            'metaKeywords',
            'metaDescription',
            'cmsHeadline',
            'cmsText',
            'template',
            'external',
            'attributeAttribute1',
            'attributeAttribute2',
            'attributeAttribute3',
            'attributeAttribute4',
            'attributeAttribute5',
            'attributeAttribute6'
        ),
    );

    /**
     * @var array
     */
    public static $defaultFieldsForCreate = array(
        'id' => array(
            'parentId'
        ),
        'boolean' => array(
            'active',
        ),
        'string' => array(
            'template',
            'attributeAttribute1',
            'attributeAttribute2',
            'attributeAttribute3',
            'attributeAttribute4',
            'attributeAttribute5',
            'attributeAttribute6'
        )
    );
}
