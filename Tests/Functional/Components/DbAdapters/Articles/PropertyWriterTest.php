<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport\Tests\Functional\Components\DbAdapters\Articles;

use Doctrine\DBAL\Connection;
use PHPUnit\Framework\TestCase;
use SwagImportExport\Components\DbAdapters\Articles\PropertyWriter;
use SwagImportExport\Tests\Helper\ContainerTrait;
use SwagImportExport\Tests\Helper\DatabaseTestCaseTrait;

class PropertyWriterTest extends TestCase
{
    use DatabaseTestCaseTrait;
    use ContainerTrait;

    public const ARTICLE_ORDERNUMBER = 'SW10002.1';
    public const ARTICLE_ORDERNUMBER_WITHOUT_PROPERTIES = 'SW10239';

    public const ARTICLE_ID_WITH_PROPERTIES = '2';
    public const ARTICLE_ID_WITHOUT_PROPERTIES = '272';

    public const NOT_EXISTING_FILTER_GROUP_NAME = 'T-Shirts';
    public const EXISTING_FILTER_GROUP_NAME = 'Edelbrände';

    public const NOT_EXISTING_VALUE_NAME = 'Not existing property';
    public const NOT_EXISTING_OPTION_NAME = 'Not existing option';

    public const INVALID_VALUE_NAME = '';
    public const INVALID_OPTION_NAME = '';

    public const EXISTING_PROPERTY_VALUE_ID = '22';

    public const NOT_EXISTING_FILTER_GROUP_ID = '999999';

    public function testWriteShouldReturnNullIfNoPropertiesWereGiven()
    {
        $propertyWriter = $this->getPropertyWriterAdapter();
        $propertyValues = null;

        $result = $propertyWriter->writeUpdateCreatePropertyGroupsFilterAndValues(
            self::ARTICLE_ID_WITH_PROPERTIES,
            self::ARTICLE_ORDERNUMBER,
            $propertyValues
        );
        static::assertNull($result);
    }

    public function testWriteShouldNotCreateNewGroupWithExistingArticleAndExistingProperties()
    {
        $propertyWriter = $this->getPropertyWriterAdapter();
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
        $dbalConnection = $this->getContainer()->get('dbal_connection');
        $importedFilter = $dbalConnection->executeQuery(
            'SELECT * FROM s_filter WHERE name = ?',
            [self::NOT_EXISTING_FILTER_GROUP_NAME]
        )->fetch(\PDO::FETCH_COLUMN);

        static::assertEmpty($importedFilter, 'Filter groups will only be created if a new product will be created.');
    }

    public function testWriteShouldUpdateGroupRelations()
    {
        $propertyWriter = $this->getPropertyWriterAdapter();
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
        $dbalConnection = $this->getContainer()->get('dbal_connection');
        $filterGroupId = $dbalConnection->executeQuery(
            'SELECT filterGroupId FROM s_articles WHERE id = ?',
            [self::ARTICLE_ID_WITHOUT_PROPERTIES]
        )->fetch(\PDO::FETCH_COLUMN);

        static::assertGreaterThan($expectedMinId, $filterGroupId, 'Could not update filter group for article.');
    }

    public function testWriteShouldCreateValue()
    {
        $propertyWriter = $this->getPropertyWriterAdapter();
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
        $dbalConnection = $this->getContainer()->get('dbal_connection');
        $createdPropertyValue = $dbalConnection->executeQuery(
            'SELECT `value` FROM s_filter_values WHERE value = ?',
            [self::NOT_EXISTING_VALUE_NAME]
        )->fetch(\PDO::FETCH_COLUMN);

        static::assertEquals(self::NOT_EXISTING_VALUE_NAME, $createdPropertyValue, 'Could not create property value.');
    }

    public function testWriteShouldCreateOption()
    {
        $propertyWriter = $this->getPropertyWriterAdapter();
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
        $dbalConnection = $this->getContainer()->get('dbal_connection');
        $createdPropertyValue = $dbalConnection->executeQuery(
            'SELECT `name` FROM s_filter_options WHERE name = ?',
            [self::NOT_EXISTING_OPTION_NAME]
        )->fetch(\PDO::FETCH_COLUMN);

        static::assertEquals(self::NOT_EXISTING_OPTION_NAME, $createdPropertyValue, 'Could not create property value.');
    }

    public function testWriteShouldThrowExceptionWithEmptyPropertyOptionName()
    {
        $propertyWriter = $this->getPropertyWriterAdapter();
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

    public function testWriteShouldCreateValueRelation()
    {
        $propertyWriter = $this->getPropertyWriterAdapter();
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
        $dbalConnection = $this->getContainer()->get('dbal_connection');
        $valueIdRelationToTestedArticle = $dbalConnection->executeQuery(
            'SELECT valueID FROM s_filter_articles WHERE articleID = ?',
            [self::ARTICLE_ID_WITHOUT_PROPERTIES]
        )->fetch(\PDO::FETCH_COLUMN);

        static::assertEquals(self::EXISTING_PROPERTY_VALUE_ID, $valueIdRelationToTestedArticle);
    }

    public function testWriteShouldCreateOptionRelation()
    {
        $propertyWriter = $this->getPropertyWriterAdapter();
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
        $dbalConnection = $this->getContainer()->get('dbal_connection');
        $createOptionRelation = $dbalConnection->executeQuery(
            'SELECT optionID FROM s_filter_relations LEFT JOIN s_articles ON s_articles.filterGroupID = s_filter_relations.groupID WHERE s_articles.id = ?',
            [self::ARTICLE_ID_WITHOUT_PROPERTIES]
        )->fetch(\PDO::FETCH_COLUMN);

        static::assertNotFalse($createOptionRelation, 'Could not update option relations.');
    }

    public function testWriteShouldCreateGroup()
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
            self::ARTICLE_ID_WITHOUT_PROPERTIES,
            self::ARTICLE_ORDERNUMBER_WITHOUT_PROPERTIES,
            $importData
        );

        /** @var Connection $dbalConnection */
        $dbalConnection = $this->getContainer()->get('dbal_connection');
        $createdGroupName = $dbalConnection->executeQuery(
            'SELECT name FROM s_filter WHERE name = ?',
            [self::NOT_EXISTING_FILTER_GROUP_NAME]
        )->fetch(\PDO::FETCH_COLUMN);

        static::assertEquals(self::NOT_EXISTING_FILTER_GROUP_NAME, $createdGroupName, 'Could not create filter group.');
    }

    /**
     * @return PropertyWriter
     */
    private function getPropertyWriterAdapter()
    {
        return $this->getContainer()->get(PropertyWriter::class);
    }
}
