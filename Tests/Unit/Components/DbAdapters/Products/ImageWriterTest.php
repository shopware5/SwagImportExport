<?php
declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport\Tests\Unit\Components\DbAdapters\Products;

use Doctrine\DBAL\Connection;
use PHPUnit\Framework\TestCase;
use Shopware\Tests\Functional\Traits\DatabaseTransactionBehaviour;
use SwagImportExport\Components\DbAdapters\Products\ImageWriter;
use SwagImportExport\Components\UploadPathProvider;
use SwagImportExport\Tests\Helper\ContainerTrait;

class ImageWriterTest extends TestCase
{
    use ContainerTrait;
    use DatabaseTransactionBehaviour;

    private const PRODUCT_ID = 44;
    private const PRODUCT_ORDERNUMBER = 'SW10043';
    private const PRODUCT_IMPORT_IMAGE = [['imageUrl' => 'media/image/bienenkleber.jpg', 'main' => '1', 'parentIndexElement' => 0]];

    private Connection $connection;

    public function setUp(): void
    {
        parent::setUp();

        $this->connection = $this->getContainer()->get(Connection::class);
    }

    public function testSetMainImageShallNotThrowTypeError(): void
    {
        $pdoMysql = $this->getContainer()->get(\Enlight_Components_Db_Adapter_Pdo_Mysql::class);
        $uploadPathProvider = $this->getContainer()->get(UploadPathProvider::class);
        $imageWriter = new ImageWriter($pdoMysql, $this->connection, $uploadPathProvider);

        static::assertGreaterThan(0, $this->countImagesMappedToProduct());
        $this->removeMappingBetweenProductAndImages();
        static::assertSame(0, $this->countImagesMappedToProduct());

        $imageWriter->write(self::PRODUCT_ID, self::PRODUCT_ORDERNUMBER, self::PRODUCT_IMPORT_IMAGE);

        static::assertSame(1, $this->countImagesMappedToProduct());
    }

    private function removeMappingBetweenProductAndImages(): void
    {
        $this->connection->executeQuery('DELETE FROM s_articles_img WHERE articleID = :productId', ['productId' => self::PRODUCT_ID]);
    }

    private function countImagesMappedToProduct(): int
    {
        $sql = 'SELECT count(*) FROM s_articles_img WHERE articleID = :productId';

        return (int) $this->connection->executeQuery($sql, ['productId' => self::PRODUCT_ID])->fetchOne();
    }
}
