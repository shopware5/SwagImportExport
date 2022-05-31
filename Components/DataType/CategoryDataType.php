<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport\Components\DataType;

class CategoryDataType
{
    /**
     * @var array<string, array<string>>
     */
    public static array $mapper = [
        'int' => [
            'categoryId',
            'parentId',
            'position',
            'active',
            'blog',
            'hideFilter',
        ],
        'string' => [
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
            'attributeAttribute6',
        ],
    ];

    /**
     * @var array<string, array<string>>
     */
    public static array $defaultFieldsForCreate = [
        'id' => [
            'parentId',
        ],
        'boolean' => [
            'active',
        ],
        'string' => [
            'template',
            'attributeAttribute1',
            'attributeAttribute2',
            'attributeAttribute3',
            'attributeAttribute4',
            'attributeAttribute5',
            'attributeAttribute6',
        ],
    ];

    private function __construct()
    {
    }
}
