<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport\Tests\Functional\Components\SwagImportExport\DbAdapters\Articles;

use Doctrine\DBAL\Connection;
use PHPUnit\Framework\TestCase;
use Shopware\Components\SwagImportExport\DbAdapters\Articles\PropertyWriter;
use SwagImportExport\Tests\Helper\DatabaseTestCaseTrait;

class PropertyWriterTest extends TestCase
{
    use DatabaseTestCaseTrait;

    const ARTICLE_ORDERNUMBER = 'SW10002.1';
    const ARTICLE_ORDERNUMBER_WITHOUT_PROPERTIES = 'SW10239';

    const ARTICLE_ID_WITH_PROPERTIES = '2';
    const ARTICLE_ID_WITHOUT_PROPERTIES = '272';

    const NOT_EXISTING_FILTER_GROUP_NAME = 'T-Shirts';
    const EXISTING_FILTER_GROUP_NAME = 'Edelbrände';

    const NOT_EXISTING_VALUE_NAME = 'Not existing property';
    const NOT_EXISTING_OPTION_NAME = 'Not existing option';

    const INVALID_VALUE_NAME = '';
    const INVALID_OPTION_NAME = '';

    const EXISTING_PROPERTY_VALUE_ID = '22';

    const NOT_EXISTING_FILTER_GROUP_ID = '999999';

    public function test_write_should_return_null_if_no_properties_were_given()
    {
        $propertyWriter = $this->createPropertyWriterAdapter();
        $propertyValues = null;

        $result = $propertyWriter->writeUpdateCreatePropertyGroupsFilterAndValues(
            self::ARTICLE_ID_WITH_PROPERTIES,
            self::ARTICLE_ORDERNUMBER,
            $propertyValues
        );
        static::assertNull($result);
    }

    public function test_write_should_not_create_new_group_with_existing_article_and_existing_properties()
    {
        $propertyWriter = $this->createPropertyWriterAdapter();
        $importData = [
            'articleId' => self::ARTICLE_ID_WITH_PROPERTIES,
            'propertyGroupName' => self::NOT_EXISTING_FILTER_GROUP_NAME,
            'propertyValueName' => self::NOT_EXISTING_VALUE_NAME,
            'propertyOptionName' => self::NOT_EXISTING_OPTION_NAME,
        ];

        $propertyWriter->writeUpdateCreatePropertyGroupsFilterAndValues(
            self::ARTICLE_ID_WITH_PROPERTIES,
            self::ARTICLE_ORDERNUMBER,
            [$importData]
        );

        /** @var Connection $dbalConnection */
        $dbalConnection = Shopware()->Container()->get('dbal_connection');
        $importedFilter = $dbalConnection->executeQuery(
            'SELECT * FROM s_filter WHERE name = ?',
            [self::NOT_EXISTING_FILTER_GROUP_NAME]
        )->fetch(\PDO::FETCH_COLUMN);

        static::assertEmpty($importedFilter, 'Filter groups will only be created if a new product will be created.');
    }

    public function test_write_should_update_group_relations()
    {
        $propertyWriter = $this->createPropertyWriterAdapter();
        $expectedMinId = 0;
        $importData = [
            [
                'articleId' => self::ARTICLE_ID_WITHOUT_PROPERTIES,
                'propertyGroupName' => self::EXISTING_FILTER_GROUP_NAME,
                'propertyValueName' => self::NOT_EXISTING_VALUE_NAME,
                'propertyOptionName' => self::NOT_EXISTING_OPTION_NAME,
            ],
        ];

        $propertyWriter->writeUpdateCreatePropertyGroupsFilterAndValues(
            self::ARTICLE_ID_WITHOUT_PROPERTIES,
            self::ARTICLE_ORDERNUMBER_WITHOUT_PROPERTIES,
            $importData
        );

        /** @var Connection $dbalConnection */
        $dbalConnection = Shopware()->Container()->get('dbal_connection');
        $filterGroupId = $dbalConnection->executeQuery(
            'SELECT filterGroupId FROM s_articles WHERE id = ?',
            [self::ARTICLE_ID_WITHOUT_PROPERTIES]
        )->fetch(\PDO::FETCH_COLUMN);

        static::assertGreaterThan($expectedMinId, $filterGroupId, 'Could not update filter group for article.');
    }

    public function test_write_should_create_value()
    {
        $propertyWriter = $this->createPropertyWriterAdapter();
        $importData = [
            [
                'articleId' => self::ARTICLE_ID_WITHOUT_PROPERTIES,
                'propertyGroupName' => self::EXISTING_FILTER_GROUP_NAME,
                'propertyValueName' => self::NOT_EXISTING_VALUE_NAME,
                'propertyOptionName' => self::NOT_EXISTING_OPTION_NAME,
            ],
        ];

        $propertyWriter->writeUpdateCreatePropertyGroupsFilterAndValues(
            self::ARTICLE_ID_WITHOUT_PROPERTIES,
            self::ARTICLE_ORDERNUMBER_WITHOUT_PROPERTIES,
            $importData
        );

        /** @var Connection $dbalConnection */
        $dbalConnection = Shopware()->Container()->get('dbal_connection');
        $createdPropertyValue = $dbalConnection->executeQuery(
            'SELECT `value` FROM s_filter_values WHERE value = ?',
            [self::NOT_EXISTING_VALUE_NAME]
        )->fetch(\PDO::FETCH_COLUMN);

        static::assertEquals(self::NOT_EXISTING_VALUE_NAME, $createdPropertyValue, 'Could not create property value.');
    }

    public function test_write_should_create_option()
    {
        $propertyWriter = $this->createPropertyWriterAdapter();
        $importData = [
            [
                'articleId' => self::ARTICLE_ID_WITHOUT_PROPERTIES,
                'propertyGroupName' => self::EXISTING_FILTER_GROUP_NAME,
                'propertyValueName' => self::NOT_EXISTING_VALUE_NAME,
                'propertyOptionName' => self::NOT_EXISTING_OPTION_NAME,
            ],
        ];

        $propertyWriter->writeUpdateCreatePropertyGroupsFilterAndValues(
            self::ARTICLE_ID_WITHOUT_PROPERTIES,
            self::ARTICLE_ORDERNUMBER_WITHOUT_PROPERTIES,
            $importData
        );

        /** @var Connection $dbalConnection */
        $dbalConnection = Shopware()->Container()->get('dbal_connection');
        $createdPropertyValue = $dbalConnection->executeQuery(
            'SELECT `name` FROM s_filter_options WHERE name = ?',
            [self::NOT_EXISTING_OPTION_NAME]
        )->fetch(\PDO::FETCH_COLUMN);

        static::assertEquals(self::NOT_EXISTING_OPTION_NAME, $createdPropertyValue, 'Could not create property value.');
    }

    public function test_write_should_throw_exception_with_empty_property_option_name()
    {
        $propertyWriter = $this->createPropertyWriterAdapter();
        $importData = [
            [
                'articleId' => self::ARTICLE_ID_WITHOUT_PROPERTIES,
                'propertyGroupName' => self::EXISTING_FILTER_GROUP_NAME,
                'propertyValueName' => self::NOT_EXISTING_VALUE_NAME,
                'propertyOptionName' => self::INVALID_OPTION_NAME,
            ],
        ];

        $this->expectException(\Exception::class);
        $propertyWriter->writeUpdateCreatePropertyGroupsFilterAndValues(
            self::ARTICLE_ID_WITHOUT_PROPERTIES,
            self::ARTICLE_ORDERNUMBER_WITHOUT_PROPERTIES,
            $importData
        );
    }

    public function test_write_should_create_value_relation()
    {
        $propertyWriter = $this->createPropertyWriterAdapter();
        $importData = [
            [
                'articleId' => self::ARTICLE_ID_WITHOUT_PROPERTIES,
                'propertyGroupName' => self::EXISTING_FILTER_GROUP_NAME,
                'propertyValueId' => self::EXISTING_PROPERTY_VALUE_ID,
            ],
        ];

        $propertyWriter->writeUpdateCreatePropertyGroupsFilterAndValues(
            self::ARTICLE_ID_WITHOUT_PROPERTIES,
            self::ARTICLE_ORDERNUMBER_WITHOUT_PROPERTIES,
            $importData
        );

        /** @var Connection $dbalConnection */
        $dbalConnection = Shopware()->Container()->get('dbal_connection');
        $valueIdRelationToTestedArticle = $dbalConnection->executeQuery(
            'SELECT valueID FROM s_filter_articles WHERE articleID = ?',
            [self::ARTICLE_ID_WITHOUT_PROPERTIES]
        )->fetch(\PDO::FETCH_COLUMN);

        static::assertEquals(self::EXISTING_PROPERTY_VALUE_ID, $valueIdRelationToTestedArticle);
    }

    public function test_write_should_create_option_relation()
    {
        $propertyWriter = $this->createPropertyWriterAdapter();
        $importData = [
            [
                'articleId' => self::ARTICLE_ID_WITHOUT_PROPERTIES,
                'propertyGroupName' => self::EXISTING_FILTER_GROUP_NAME,
                'propertyValueId' => self::EXISTING_PROPERTY_VALUE_ID,
            ],
        ];

        $propertyWriter->writeUpdateCreatePropertyGroupsFilterAndValues(
            self::ARTICLE_ID_WITHOUT_PROPERTIES,
            self::ARTICLE_ORDERNUMBER_WITHOUT_PROPERTIES,
            $importData
        );

        /** @var Connection $dbalConnection */
        $dbalConnection = Shopware()->Container()->get('dbal_connection');
        $createOptionRelation = $dbalConnection->executeQuery(
            'SELECT optionID FROM s_filter_relations LEFT JOIN s_articles ON s_articles.filterGroupID = s_filter_relations.groupID WHERE s_articles.id = ?',
            [self::ARTICLE_ID_WITHOUT_PROPERTIES]
        )->fetch(\PDO::FETCH_COLUMN);

        static::assertNotFalse($createOptionRelation, 'Could not update option relations.');
    }

    public function test_write_should_create_group()
    {
        $propertyWriter = $this->createPropertyWriterAdapter();
        $importData = [
            [
                'propertyGroupName' => self::NOT_EXISTING_FILTER_GROUP_NAME,
                'propertyValueName' => self::NOT_EXISTING_VALUE_NAME,
                'propertyOptionName' => self::NOT_EXISTING_OPTION_NAME,
            ],
        ];

        $propertyWriter->writeUpdateCreatePropertyGroupsFilterAndValues(
            self::ARTICLE_ID_WITHOUT_PROPERTIES,
            self::ARTICLE_ORDERNUMBER_WITHOUT_PROPERTIES,
            $importData
        );

        /** @var Connection $dbalConnection */
        $dbalConnection = Shopware()->Container()->get('dbal_connection');
        $createdGroupName = $dbalConnection->executeQuery(
            'SELECT name FROM s_filter WHERE name = ?',
            [self::NOT_EXISTING_FILTER_GROUP_NAME]
        )->fetch(\PDO::FETCH_COLUMN);

        static::assertEquals(self::NOT_EXISTING_FILTER_GROUP_NAME, $createdGroupName, 'Could not create filter group.');
    }

    /**
     * @return PropertyWriter
     */
    private function createPropertyWriterAdapter()
    {
        return PropertyWriter::createFromGlobalSingleton();
    }
}
