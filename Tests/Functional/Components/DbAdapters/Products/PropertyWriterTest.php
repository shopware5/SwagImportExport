<?php
declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport\Tests\Functional\Components\DbAdapters\Products;

use PHPUnit\Framework\TestCase;
use SwagImportExport\Components\DbAdapters\Products\PropertyWriter;
use SwagImportExport\Tests\Helper\ContainerTrait;
use SwagImportExport\Tests\Helper\DatabaseTestCaseTrait;

class PropertyWriterTest extends TestCase
{
    use DatabaseTestCaseTrait;
    use ContainerTrait;

    public const PRODUCT_ORDERNUMBER = 'SW10002.1';
    public const PRODUCT_ORDERNUMBER_WITHOUT_PROPERTIES = 'SW10239';

    public const PRODUCT_ID_WITH_PROPERTIES = 2;
    public const PRODUCT_ID_WITHOUT_PROPERTIES = 272;

    public const NOT_EXISTING_FILTER_GROUP_NAME = 'T-Shirts';
    public const EXISTING_FILTER_GROUP_NAME = 'Edelbrände';

    public const NOT_EXISTING_VALUE_NAME = 'Not existing property';
    public const NOT_EXISTING_OPTION_NAME = 'Not existing option';

    public const INVALID_VALUE_NAME = '';
    public const INVALID_OPTION_NAME = '';

    public const EXISTING_PROPERTY_VALUE_ID = '22';

    public const NOT_EXISTING_FILTER_GROUP_ID = '999999';

    public function testWriteShouldNotCreateNewGroupWithExistingProductAndExistingProperties(): void
    {
        $propertyWriter = $this->getPropertyWriterAdapter();
        $importData = [
            'articleId' => self::PRODUCT_ID_WITH_PROPERTIES,
            'propertyGroupName' => self::NOT_EXISTING_FILTER_GROUP_NAME,
            'propertyValueName' => self::NOT_EXISTING_VALUE_NAME,
            'propertyOptionName' => self::NOT_EXISTING_OPTION_NAME,
        ];

        $propertyWriter->writeUpdateCreatePropertyGroupsFilterAndValues(
            self::PRODUCT_ID_WITH_PROPERTIES,
            self::PRODUCT_ORDERNUMBER,
            [$importData]
        );

        $dbalConnection = $this->getContainer()->get('dbal_connection');
        $importedFilter = $dbalConnection->executeQuery(
            'SELECT * FROM s_filter WHERE name = ?',
            [self::NOT_EXISTING_FILTER_GROUP_NAME]
        )->fetchOne();

        static::assertEmpty($importedFilter, 'Filter groups will only be created if a new product will be created.');
    }

    public function testWriteShouldUpdateGroupRelations(): void
    {
        $propertyWriter = $this->getPropertyWriterAdapter();
        $expectedMinId = 0;
        $importData = [
            [
                'articleId' => self::PRODUCT_ID_WITHOUT_PROPERTIES,
                'propertyGroupName' => self::EXISTING_FILTER_GROUP_NAME,
                'propertyValueName' => self::NOT_EXISTING_VALUE_NAME,
                'propertyOptionName' => self::NOT_EXISTING_OPTION_NAME,
            ],
        ];

        $propertyWriter->writeUpdateCreatePropertyGroupsFilterAndValues(
            self::PRODUCT_ID_WITHOUT_PROPERTIES,
            self::PRODUCT_ORDERNUMBER_WITHOUT_PROPERTIES,
            $importData
        );

        $dbalConnection = $this->getContainer()->get('dbal_connection');
        $filterGroupId = $dbalConnection->executeQuery(
            'SELECT filterGroupId FROM s_articles WHERE id = ?',
            [self::PRODUCT_ID_WITHOUT_PROPERTIES]
        )->fetchOne();

        static::assertGreaterThan($expectedMinId, $filterGroupId, 'Could not update filter group for article.');
    }

    public function testWriteShouldCreateValue(): void
    {
        $propertyWriter = $this->getPropertyWriterAdapter();
        $importData = [
            [
                'articleId' => self::PRODUCT_ID_WITHOUT_PROPERTIES,
                'propertyGroupName' => self::EXISTING_FILTER_GROUP_NAME,
                'propertyValueName' => self::NOT_EXISTING_VALUE_NAME,
                'propertyOptionName' => self::NOT_EXISTING_OPTION_NAME,
            ],
        ];

        $propertyWriter->writeUpdateCreatePropertyGroupsFilterAndValues(
            self::PRODUCT_ID_WITHOUT_PROPERTIES,
            self::PRODUCT_ORDERNUMBER_WITHOUT_PROPERTIES,
            $importData
        );

        $dbalConnection = $this->getContainer()->get('dbal_connection');
        $createdPropertyValue = $dbalConnection->executeQuery(
            'SELECT `value` FROM s_filter_values WHERE value = ?',
            [self::NOT_EXISTING_VALUE_NAME]
        )->fetchOne();

        static::assertEquals(self::NOT_EXISTING_VALUE_NAME, $createdPropertyValue, 'Could not create property value.');
    }

    public function testWriteShouldCreateOption(): void
    {
        $propertyWriter = $this->getPropertyWriterAdapter();
        $importData = [
            [
                'articleId' => self::PRODUCT_ID_WITHOUT_PROPERTIES,
                'propertyGroupName' => self::EXISTING_FILTER_GROUP_NAME,
                'propertyValueName' => self::NOT_EXISTING_VALUE_NAME,
                'propertyOptionName' => self::NOT_EXISTING_OPTION_NAME,
            ],
        ];

        $propertyWriter->writeUpdateCreatePropertyGroupsFilterAndValues(
            self::PRODUCT_ID_WITHOUT_PROPERTIES,
            self::PRODUCT_ORDERNUMBER_WITHOUT_PROPERTIES,
            $importData
        );

        $dbalConnection = $this->getContainer()->get('dbal_connection');
        $createdPropertyValue = $dbalConnection->executeQuery(
            'SELECT `name` FROM s_filter_options WHERE name = ?',
            [self::NOT_EXISTING_OPTION_NAME]
        )->fetchOne();

        static::assertEquals(self::NOT_EXISTING_OPTION_NAME, $createdPropertyValue, 'Could not create property value.');
    }

    public function testWriteShouldThrowExceptionWithEmptyPropertyOptionName(): void
    {
        $propertyWriter = $this->getPropertyWriterAdapter();
        $importData = [
            [
                'articleId' => self::PRODUCT_ID_WITHOUT_PROPERTIES,
                'propertyGroupName' => self::EXISTING_FILTER_GROUP_NAME,
                'propertyValueName' => self::NOT_EXISTING_VALUE_NAME,
                'propertyOptionName' => self::INVALID_OPTION_NAME,
            ],
        ];

        $this->expectException(\Exception::class);
        $propertyWriter->writeUpdateCreatePropertyGroupsFilterAndValues(
            self::PRODUCT_ID_WITHOUT_PROPERTIES,
            self::PRODUCT_ORDERNUMBER_WITHOUT_PROPERTIES,
            $importData
        );
    }

    public function testWriteShouldCreateValueRelation(): void
    {
        $propertyWriter = $this->getPropertyWriterAdapter();
        $importData = [
            [
                'articleId' => self::PRODUCT_ID_WITHOUT_PROPERTIES,
                'propertyGroupName' => self::EXISTING_FILTER_GROUP_NAME,
                'propertyValueId' => self::EXISTING_PROPERTY_VALUE_ID,
            ],
        ];

        $propertyWriter->writeUpdateCreatePropertyGroupsFilterAndValues(
            self::PRODUCT_ID_WITHOUT_PROPERTIES,
            self::PRODUCT_ORDERNUMBER_WITHOUT_PROPERTIES,
            $importData
        );

        $dbalConnection = $this->getContainer()->get('dbal_connection');
        $valueIdRelationToTestedProduct = $dbalConnection->executeQuery(
            'SELECT valueID FROM s_filter_articles WHERE articleID = ?',
            [self::PRODUCT_ID_WITHOUT_PROPERTIES]
        )->fetchOne();

        static::assertEquals(self::EXISTING_PROPERTY_VALUE_ID, $valueIdRelationToTestedProduct);
    }

    public function testWriteShouldCreateOptionRelation(): void
    {
        $propertyWriter = $this->getPropertyWriterAdapter();
        $importData = [
            [
                'articleId' => self::PRODUCT_ID_WITHOUT_PROPERTIES,
                'propertyGroupName' => self::EXISTING_FILTER_GROUP_NAME,
                'propertyValueId' => self::EXISTING_PROPERTY_VALUE_ID,
            ],
        ];

        $propertyWriter->writeUpdateCreatePropertyGroupsFilterAndValues(
            self::PRODUCT_ID_WITHOUT_PROPERTIES,
            self::PRODUCT_ORDERNUMBER_WITHOUT_PROPERTIES,
            $importData
        );

        $dbalConnection = $this->getContainer()->get('dbal_connection');
        $createOptionRelation = $dbalConnection->executeQuery(
            'SELECT optionID FROM s_filter_relations LEFT JOIN s_articles ON s_articles.filterGroupID = s_filter_relations.groupID WHERE s_articles.id = ?',
            [self::PRODUCT_ID_WITHOUT_PROPERTIES]
        )->fetchOne();

        static::assertNotFalse($createOptionRelation, 'Could not update option relations.');
    }

    public function testWriteShouldCreateGroup(): void
    {
        $propertyWriter = $this->getPropertyWriterAdapter();
        $importData = [
            [
                'propertyGroupName' => self::NOT_EXISTING_FILTER_GROUP_NAME,
                'propertyValueName' => self::NOT_EXISTING_VALUE_NAME,
                'propertyOptionName' => self::NOT_EXISTING_OPTION_NAME,
            ],
        ];

        $propertyWriter->writeUpdateCreatePropertyGroupsFilterAndValues(
            self::PRODUCT_ID_WITHOUT_PROPERTIES,
            self::PRODUCT_ORDERNUMBER_WITHOUT_PROPERTIES,
            $importData
        );

        $dbalConnection = $this->getContainer()->get('dbal_connection');
        $createdGroupName = $dbalConnection->executeQuery(
            'SELECT name FROM s_filter WHERE name = ?',
            [self::NOT_EXISTING_FILTER_GROUP_NAME]
        )->fetchOne();

        static::assertEquals(self::NOT_EXISTING_FILTER_GROUP_NAME, $createdGroupName, 'Could not create filter group.');
    }

    private function getPropertyWriterAdapter(): PropertyWriter
    {
        return $this->getContainer()->get(PropertyWriter::class);
    }
}
