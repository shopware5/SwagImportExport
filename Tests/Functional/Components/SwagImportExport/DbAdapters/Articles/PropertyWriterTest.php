<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport\Tests\Functional\Components\SwagImportExport\DbAdapters\Articles;

use Shopware\Components\Model\ModelManager;
use Shopware\Components\SwagImportExport\DbAdapters\Articles\PropertyWriter;
use Shopware\Components\SwagImportExport\Exception\AdapterException;

class PropertyWriterTest extends \PHPUnit_Framework_TestCase
{
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

    /**
     * @var PropertyWriter
     */
    private $propertyWriter;

    /**
     * @var ModelManager
     */
    private $modelManager;

    protected function setUp()
    {
        $this->propertyWriter = PropertyWriter::createFromGlobalSingleton();

        $this->modelManager = Shopware()->Container()->get('models');
        $this->modelManager->beginTransaction();
    }

    protected function tearDown()
    {
        $this->modelManager->rollback();
    }

    public function test_write_should_return_null_if_no_properties_were_given()
    {
        $propertyValues = null;

        $result = $this->propertyWriter->writeUpdateCreatePropertyGroupsFilterAndValues(self::ARTICLE_ID_WITH_PROPERTIES, self::ARTICLE_ORDERNUMBER, $propertyValues);
        $this->assertNull($result);
    }

    public function test_write_should_not_create_new_group_with_existing_article_and_existing_properties()
    {
        $importData = [
            'articleId' => self::ARTICLE_ID_WITH_PROPERTIES,
            'propertyGroupName' => self::NOT_EXISTING_FILTER_GROUP_NAME,
            'propertyValueName' => self::NOT_EXISTING_VALUE_NAME,
            'propertyOptionName' => self::NOT_EXISTING_OPTION_NAME,
        ];

        $this->propertyWriter->writeUpdateCreatePropertyGroupsFilterAndValues(self::ARTICLE_ID_WITH_PROPERTIES, self::ARTICLE_ORDERNUMBER, [$importData]);

        $importedFilter = $this->modelManager->getConnection()
            ->executeQuery('SELECT * FROM s_filter WHERE name = ?', [self::NOT_EXISTING_FILTER_GROUP_NAME])
            ->fetchAll();

        $this->assertEmpty($importedFilter, "Filter groups will only be created if a new product will be created.");
    }

    public function test_write_should_throw_an_exception_with_empty_property_value_name()
    {
        $importData = [
            'articleId' => self::ARTICLE_ID_WITH_PROPERTIES,
            'propertyGroupName' => self::EXISTING_FILTER_GROUP_NAME,
            'propertyGroupId' => self::NOT_EXISTING_FILTER_GROUP_ID
        ];

        try {
            $this->propertyWriter->writeUpdateCreatePropertyGroupsFilterAndValues(self::ARTICLE_ID_WITH_PROPERTIES, self::ARTICLE_ORDERNUMBER, $importData);
        } catch (\Exception $e) {
            $this->assertInstanceOf(AdapterException::class, $e);
        }
    }

    public function test_write_should_update_group_relations()
    {
        $expectedMinId = 0;
        $importData = [
            [
                'articleId' => self::ARTICLE_ID_WITHOUT_PROPERTIES,
                'propertyGroupName' => self::EXISTING_FILTER_GROUP_NAME,
                'propertyValueName' => self::NOT_EXISTING_VALUE_NAME,
                'propertyOptionName' => self::NOT_EXISTING_OPTION_NAME
            ]
        ];

        $this->propertyWriter->writeUpdateCreatePropertyGroupsFilterAndValues(self::ARTICLE_ID_WITHOUT_PROPERTIES, self::ARTICLE_ORDERNUMBER_WITHOUT_PROPERTIES, $importData);

        $filterGroupId = $this->modelManager->getConnection()
            ->executeQuery('SELECT filterGroupId FROM s_articles WHERE id = ?', [ self::ARTICLE_ID_WITHOUT_PROPERTIES ])
            ->fetchColumn();

        $this->assertGreaterThan($expectedMinId, $filterGroupId, 'Could not update filter group for article.');
    }

    public function test_write_should_create_value()
    {
        $importData = [
            [
                'articleId' => self::ARTICLE_ID_WITHOUT_PROPERTIES,
                'propertyGroupName' => self::EXISTING_FILTER_GROUP_NAME,
                'propertyValueName' => self::NOT_EXISTING_VALUE_NAME,
                'propertyOptionName' => self::NOT_EXISTING_OPTION_NAME
            ]
        ];

        $this->propertyWriter->writeUpdateCreatePropertyGroupsFilterAndValues(self::ARTICLE_ID_WITHOUT_PROPERTIES, self::ARTICLE_ORDERNUMBER_WITHOUT_PROPERTIES, $importData);

        $createdPropertyValue = $this->modelManager->getConnection()
            ->executeQuery('SELECT `value` FROM s_filter_values WHERE value = ?', [ self::NOT_EXISTING_VALUE_NAME ])
            ->fetchColumn();

        $this->assertEquals(self::NOT_EXISTING_VALUE_NAME, $createdPropertyValue, 'Could not create property value.');
    }

    public function test_write_should_throw_exception_with_empty_property_value_name()
    {
        $importData = [
            [
                'articleId' => self::ARTICLE_ID_WITHOUT_PROPERTIES,
                'propertyGroupName' => self::EXISTING_FILTER_GROUP_NAME,
                'propertyValueName' => '',
                'propertyOptionName' => self::NOT_EXISTING_OPTION_NAME
            ]
        ];

        try {
            $this->propertyWriter->writeUpdateCreatePropertyGroupsFilterAndValues(self::ARTICLE_ID_WITHOUT_PROPERTIES, self::ARTICLE_ORDERNUMBER_WITHOUT_PROPERTIES, $importData);
        } catch (\Exception $e) {
        } finally {
            $this->assertInstanceOf(AdapterException::class, $e);
        }
    }

    public function test_write_should_create_option()
    {
        $importData = [
            [
                'articleId' => self::ARTICLE_ID_WITHOUT_PROPERTIES,
                'propertyGroupName' => self::EXISTING_FILTER_GROUP_NAME,
                'propertyValueName' => self::NOT_EXISTING_VALUE_NAME,
                'propertyOptionName' => self::NOT_EXISTING_OPTION_NAME
            ]
        ];

        $this->propertyWriter->writeUpdateCreatePropertyGroupsFilterAndValues(self::ARTICLE_ID_WITHOUT_PROPERTIES, self::ARTICLE_ORDERNUMBER_WITHOUT_PROPERTIES, $importData);

        $createdPropertyValue = $this->modelManager->getConnection()
            ->executeQuery('SELECT `name` FROM s_filter_options WHERE name = ?', [ self::NOT_EXISTING_OPTION_NAME ])
            ->fetchColumn();

        $this->assertEquals(self::NOT_EXISTING_OPTION_NAME, $createdPropertyValue, 'Could not create property value.');
    }

    public function test_write_should_throw_exception_with_empty_property_option_name()
    {
        $importData = [
            [
                'articleId' => self::ARTICLE_ID_WITHOUT_PROPERTIES,
                'propertyGroupName' => self::EXISTING_FILTER_GROUP_NAME,
                'propertyValueName' => self::NOT_EXISTING_VALUE_NAME,
                'propertyOptionName' => self::INVALID_OPTION_NAME
            ]
        ];

        try {
            $this->propertyWriter->writeUpdateCreatePropertyGroupsFilterAndValues(self::ARTICLE_ID_WITHOUT_PROPERTIES, self::ARTICLE_ORDERNUMBER_WITHOUT_PROPERTIES, $importData);
        } catch (\Exception $e) {
        } finally {
            $this->assertInstanceOf(AdapterException::class, $e);
        }
    }

    public function test_write_should_create_value_relation()
    {
        $importData = [
            [
                'articleId' => self::ARTICLE_ID_WITHOUT_PROPERTIES,
                'propertyGroupName' => self::EXISTING_FILTER_GROUP_NAME,
                'propertyValueId' => self::EXISTING_PROPERTY_VALUE_ID
            ]
        ];

        $this->propertyWriter->writeUpdateCreatePropertyGroupsFilterAndValues(self::ARTICLE_ID_WITHOUT_PROPERTIES, self::ARTICLE_ORDERNUMBER_WITHOUT_PROPERTIES, $importData);

        $valueIdRelationToTestedArticle = $this->modelManager->getConnection()
            ->executeQuery('SELECT valueID FROM s_filter_articles WHERE articleID = ?', [ self::ARTICLE_ID_WITHOUT_PROPERTIES ])
            ->fetchColumn();

        $this->assertEquals(self::EXISTING_PROPERTY_VALUE_ID, $valueIdRelationToTestedArticle);
    }

    public function test_write_should_create_option_relation()
    {
        $importData = [
            [
                'articleId' => self::ARTICLE_ID_WITHOUT_PROPERTIES,
                'propertyGroupName' => self::EXISTING_FILTER_GROUP_NAME,
                'propertyValueId' => self::EXISTING_PROPERTY_VALUE_ID
            ]
        ];

        $this->propertyWriter->writeUpdateCreatePropertyGroupsFilterAndValues(self::ARTICLE_ID_WITHOUT_PROPERTIES, self::ARTICLE_ORDERNUMBER_WITHOUT_PROPERTIES, $importData);

        $createOptionRelation = $this->modelManager->getConnection()
            ->executeQuery('SELECT optionID FROM s_filter_relations LEFT JOIN s_articles ON s_articles.filterGroupID = s_filter_relations.groupID WHERE s_articles.id = ?', [ self::ARTICLE_ID_WITHOUT_PROPERTIES ])
            ->fetchColumn();

        $this->assertNotFalse($createOptionRelation, "Could not update option relations.");
    }

    public function test_write_should_create_group()
    {
        $importData = [
            [
                'propertyGroupName' => self::NOT_EXISTING_FILTER_GROUP_NAME,
                'propertyValueName' => self::NOT_EXISTING_VALUE_NAME,
                'propertyOptionName' => self::NOT_EXISTING_OPTION_NAME
            ]
        ];

        $this->propertyWriter->writeUpdateCreatePropertyGroupsFilterAndValues(
            self::ARTICLE_ID_WITHOUT_PROPERTIES,
            self::ARTICLE_ORDERNUMBER_WITHOUT_PROPERTIES,
            $importData
        );

        $createdGroupName = $this->modelManager->getConnection()
            ->executeQuery('SELECT name FROM s_filter WHERE name = ?', [ self::NOT_EXISTING_FILTER_GROUP_NAME ])->fetchColumn();

        $this->assertEquals(self::NOT_EXISTING_FILTER_GROUP_NAME, $createdGroupName, 'Could not create filter group.');
    }
}
