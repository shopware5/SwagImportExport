<?php
declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport\Tests\Functional\Components;

use Doctrine\ORM\Mapping\ClassMetadata;
use PHPUnit\Framework\TestCase;
use Shopware\Components\Model\ModelManager;
use Shopware\Models\Article\Article;
use Shopware\Models\Customer\Customer;
use SwagImportExport\Components\DbalHelper;
use SwagImportExport\Tests\Helper\ContainerTrait;

class DbalHelperTest extends TestCase
{
    use ContainerTrait;
    public const EXAMPLE_TABLE = 'example_table';

    public const DB_COLUMN_NAME = 'name_in_db_one';
    public const ANOTHER_DB_COLUMN_NAME = 'name_in_db_two';

    public const MODEL_FIELD_NAME = 'name_in_model_one';
    public const ANOTHER_MODEL_FIELD_NAME = 'name_in_model_two';

    public const IMPORT_VALUE = 'field value 01';
    public const ANOTHER_IMPORT_VALUE = 'field value 02';

    public const UPDATE_RECORD_BY_ID = 999;

    private ModelManager $modelManager;

    private DbalHelper $dbalHelper;

    protected function setUp(): void
    {
        parent::setUp();

        $this->modelManager = $this->getContainer()->get('models');
        $this->modelManager->beginTransaction();

        $modelManagerStub = $this->getModelManagerStub();
        $connection = $this->getContainer()->get('dbal_connection');
        $eventManager = $this->getContainer()->get('events');

        $this->dbalHelper = new DbalHelper($connection, $modelManagerStub, $eventManager);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->modelManager->rollback();
    }

    public function testGetQueryBuilderForEntityShouldReturnInsertQuery(): void
    {
        $expectedSQL = 'INSERT INTO example_table (`name_in_db_one`, `name_in_db_two`) VALUES(:dcValue1, :dcValue2)';
        $importData = [
            self::MODEL_FIELD_NAME => self::IMPORT_VALUE,
            self::ANOTHER_MODEL_FIELD_NAME => self::ANOTHER_IMPORT_VALUE,
        ];

        $builder = $this->dbalHelper->getQueryBuilderForEntity($importData, Article::class, null);

        static::assertEquals($expectedSQL, $builder->getSQL(), 'Could not generate insert sql statement.');
    }

    public function testGetQueryBuilderForEntityShouldReturnUpdateQuery(): void
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

        $builder = $this->dbalHelper->getQueryBuilderForEntity($givenData, Article::class, self::UPDATE_RECORD_BY_ID);

        static::assertEquals($expectedSQL, $builder->getSQL(), "SQL UPDATE statement wasn't generated correctly");
        static::assertEquals($expectedSQLParams, $builder->getParameters(), "Parameters for query builder wasn't set correctly.");
    }

    private function getModelManagerStub(): ModelManager
    {
        $modelManagerStub = $this->getMockBuilder(ModelManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $modelManagerStub->method('getClassMetadata')
            ->willReturn($this->createClassMetadataObject());

        return $modelManagerStub;
    }

    private function createClassMetadataObject(): ClassMetadata
    {
        $classMetaData = new ClassMetadata(Customer::class, null);

        $classMetaData->table = [
            'name' => self::EXAMPLE_TABLE,
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
