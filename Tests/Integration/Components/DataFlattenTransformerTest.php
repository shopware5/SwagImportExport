<?php
declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport\Tests\Integration\Components;

use PHPUnit\Framework\TestCase;
use Shopware\Tests\Functional\Traits\DatabaseTransactionBehaviour;
use SwagImportExport\Components\Profile\Profile;
use SwagImportExport\Components\Transformers\FlattenTransformer;
use SwagImportExport\Tests\Helper\ContainerTrait;

class DataFlattenTransformerTest extends TestCase
{
    use DatabaseTransactionBehaviour;
    use ContainerTrait;

    public function getExampleExportData(): string
    {
        return '{"id":"root","name":"Root","type":"node","children":[{"id":"537359399c80a","name":"Header","index":0,"type":"node","children":[{"id":"537385ed7c799","name":"HeaderChild","index":0,"type":"node","shopwareField":""}]},{"id":"537359399c8b7","name":"categories","index":1,"type":"node","children":[{"id":"537359399c90d","name":"category","index":0,"type":"iteration","adapter":"default","attributes":null,"children":[{"id":"53e9f539a997d","type":"leaf","index":0,"name":"categoryId","shopwareField":"categoryId"},{"id":"53e0a853f1b98","type":"leaf","index":1,"name":"parentID","shopwareField":"parentId"},{"id":"53e0cf5cad595","type":"leaf","index":2,"name":"description","shopwareField":"name"},{"id":"53e9f69bf2edb","type":"leaf","index":3,"name":"position","shopwareField":"position"},{"id":"53e0d1414b0ad","type":"leaf","index":4,"name":"metatitle","shopwareField":"metaTitle"},{"id":"53e0d1414b0d7","type":"leaf","index":5,"name":"metakeywords","shopwareField":"metaKeywords"},{"id":"53e0d17da1f06","type":"leaf","index":6,"name":"metadescription","shopwareField":"metaDescription"},{"id":"53e9f5c0eedaf","type":"leaf","index":7,"name":"cmsheadline","shopwareField":"cmsHeadline"},{"id":"53e9f5d80f10f","type":"leaf","index":8,"name":"cmstext","shopwareField":"cmsText"},{"id":"53e9f5e603ffe","type":"leaf","index":9,"name":"template","shopwareField":"template"},{"id":"53e9f5f87c87a","type":"leaf","index":10,"name":"active","shopwareField":"active"},{"id":"53e9f609c56eb","type":"leaf","index":11,"name":"blog","shopwareField":"blog"},{"id":"53e9f62a03f55","type":"leaf","index":13,"name":"external","shopwareField":"external"},{"id":"53e9f637aa1fe","type":"leaf","index":14,"name":"hidefilter","shopwareField":"hideFilter"},{"id":"541c35c378bc9","type":"leaf","index":15,"name":"attribute_attribute1","shopwareField":"attributeAttribute1"},{"id":"541c36d0bba0f","type":"leaf","index":16,"name":"attribute_attribute2","shopwareField":"attributeAttribute2"},{"id":"541c36d63fac6","type":"leaf","index":17,"name":"attribute_attribute3","shopwareField":"attributeAttribute3"},{"id":"541c36da52222","type":"leaf","index":18,"name":"attribute_attribute4","shopwareField":"attributeAttribute4"},{"id":"541c36dc540e3","type":"leaf","index":19,"name":"attribute_attribute5","shopwareField":"attributeAttribute5"},{"id":"541c36dd9e130","type":"leaf","index":20,"name":"attribute_attribute6","shopwareField":"attributeAttribute6"},{"id":"54dc86ff4bee5","name":"CustomerGroups","index":21,"type":"iteration","adapter":"customerGroups","parentKey":"categoryId","shopwareField":"","children":[{"id":"54dc87118ad11","type":"leaf","index":0,"name":"CustomerGroup","shopwareField":"customerGroupId"}]}]}]}]}';
    }

    public function testExportHeader(): void
    {
        $jsonTree = $this->getExampleExportData();

        $expectedData = [
            'categoryId',
            'parentID',
            'description',
            'position',
            'metatitle',
            'metakeywords',
            'metadescription',
            'cmsheadline',
            'cmstext',
            'template',
            'active',
            'blog',
            'external',
            'hidefilter',
            'attribute_attribute1',
            'attribute_attribute2',
            'attribute_attribute3',
            'attribute_attribute4',
            'attribute_attribute5',
            'attribute_attribute6',
            'CustomerGroup',
        ];

        $flattenTransformer = new FlattenTransformer(
            $this->getContainer()->get('events'),
            $this->getContainer()->get('models'),
            $this->getContainer()->get('db')
        );
        $profileEntity = new \SwagImportExport\Models\Profile();
        $profileEntity->setTree($jsonTree);
        $profile = new Profile($profileEntity);
        $flattenTransformer->initialize($profile);

        $data = $flattenTransformer->composeHeader();
        static::assertEquals($expectedData, $data);
    }
}
