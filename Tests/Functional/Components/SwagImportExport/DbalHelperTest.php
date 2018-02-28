<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Functional\Components\SwagImportExport;

use Doctrine\ORM\Mapping\ClassMetadata;
use PHPUnit\Framework\TestCase;
use Shopware\Components\Model\ModelManager;
use Shopware\Components\SwagImportExport\DbalHelper;
use Shopware\Models\Article\Article;

class DbalHelperTest extends TestCase
{
    const EXAMPLE_TABLE = 'example_table';

    const DB_COLUMN_NAME = 'name_in_db_one';
    const ANOTHER_DB_COLUMN_NAME = 'name_in_db_two';

    const MODEL_FIELD_NAME = 'name_in_model_one';
    const ANOTHER_MODEL_FIELD_NAME = 'name_in_model_two';

    const IMPORT_VALUE = 'field value 01';
    const ANOTHER_IMPORT_VALUE = 'field value 02';

    const UPDATE_RECORD_BY_ID = 999;
    const CREATE_INSERT_STATEMENT = null;

    /**
     * @var ModelManager
     */
    private $modelManager;

    /**
     * @var DbalHelper
     */
    private $SUT;

    protected function setUp()
    {
        parent::setUp();

        $this->modelManager = Shopware()->Container()->get('models');
        $this->modelManager->beginTransaction();

        $modelManagerStub = $this->getModelManagerStub();
        $connection = Shopware()->Container()->get('dbal_connection');
        $eventManager = Shopware()->Container()->get('events');

        $this->SUT = new DbalHelper($connection, $modelManagerStub, $eventManager);
    }

    protected function tearDown()
    {
        parent::tearDown();
        $this->modelManager->rollback();
    }

    public function test_get_query_builder_for_entity_should_return_insert_query()
    {
        $expectedSQL = 'INSERT INTO example_table (`name_in_db_one`, `name_in_db_two`) VALUES(:dcValue1, :dcValue2)';
        $importData = [
            self::MODEL_FIELD_NAME => self::IMPORT_VALUE,
            self::ANOTHER_MODEL_FIELD_NAME => self::ANOTHER_IMPORT_VALUE,
        ];

        $builder = $this->SUT->getQueryBuilderForEntity($importData, Article::class, self::CREATE_INSERT_STATEMENT);

        $this->assertEquals($expectedSQL, $builder->getSQL(), 'Could not generate insert sql statement.');
    }

    public function test_get_query_builder_for_entity_should_return_update_query()
    {
        $expectedSQL = 'UPDATE example_table SET id = :dcValue1, `name_in_db_one` = :dcValue2, `name_in_db_two` = :dcValue3 WHERE id = :dcValue1';
        $expectedSQLParams = [
            'dcValue1' => self::UPDATE_RECORD_BY_ID,
            'dcValue2' => self::IMPORT_VALUE,
            'dcValue3' => self::ANOTHER_IMPORT_VALUE,
        ];

        $givenData = [
            self::MODEL_FIELD_NAME => self::IMPORT_VALUE,
            self::ANOTHER_MODEL_FIELD_NAME => self::ANOTHER_IMPORT_VALUE,
        ];

        $builder = $this->SUT->getQueryBuilderForEntity($givenData, Article::class, self::UPDATE_RECORD_BY_ID);

        $this->assertEquals($expectedSQL, $builder->getSQL(), "SQL UPDATE statement wasn't generated correctly");
        $this->assertEquals($expectedSQLParams, $builder->getParameters(), "Parameters for query builder wasn't set correctly.");
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    private function getModelManagerStub()
    {
        $modelManagerStub = $this->getMockBuilder(ModelManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $modelManagerStub->method('getClassMetadata')
            ->willReturn($this->createClassMetadataObject());

        return $modelManagerStub;
    }

    /**
     * @return ClassMetadata
     */
    private function createClassMetadataObject()
    {
        $classMetaData = new ClassMetadata('ExampleEntity', null);

        $classMetaData->table = [
            'name' => 'example_table',
        ];

        $classMetaData->fieldMappings = [
            self::MODEL_FIELD_NAME => [
                'fieldName' => self::MODEL_FIELD_NAME,
                'columnName' => self::DB_COLUMN_NAME,
                'type' => 'string',
            ],
            self::ANOTHER_MODEL_FIELD_NAME => [
                'fieldName' => self::ANOTHER_MODEL_FIELD_NAME,
                'columnName' => self::ANOTHER_DB_COLUMN_NAME,
                'type' => 'string',
            ],
        ];

        return $classMetaData;
    }
}
