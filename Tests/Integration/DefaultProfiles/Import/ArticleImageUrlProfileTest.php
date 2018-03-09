<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport\Tests\Integration\DefaultProfiles\Import;

use PHPUnit\Framework\TestCase;
use SwagImportExport\Tests\Helper\CommandTestCaseTrait;
use SwagImportExport\Tests\Helper\DatabaseTestCaseTrait;
use SwagImportExport\Tests\Integration\DefaultProfiles\DefaultProfileImportTestCaseTrait;

class ArticleImageUrlProfileTest extends TestCase
{
    use DatabaseTestCaseTrait;
    use CommandTestCaseTrait;
    use DefaultProfileImportTestCaseTrait;

    public function test_import_should_add_new_image_to_article()
    {
        $imagePath = 'file://' . realpath(__DIR__) . '/../../../Helper/ImportFiles/sw-icon_blue128.png';
        $importFile = $this->getImportFile('article_image_url_create.csv');

        // writes importdata with actual imagePath to csv to use internal file for import test
        file_put_contents(
            $importFile,
            "\r\n" . implode(';', ['SW10001', 'SW10001', $imagePath]),
            FILE_APPEND
        );

        $this->runCommand("sw:import:import -p default_article_images_url {$importFile}");

        $articleResult = $this->executeQuery("SELECT articleID FROM s_articles_details WHERE orderNumber='SW10001'", \PDO::FETCH_COLUMN);
        $images = $this->executeQuery("SELECT * FROM s_articles_img WHERE articleID = '{$articleResult[0]}'", \PDO::FETCH_ASSOC);

        $this->assertEquals($articleResult[0], $images[1]['articleID']);
        $this->assertEquals(2, $images[1]['position']);
        $this->assertStringStartsWith('sw-icon_blue', $images[1]['img']);

        // removes generated import line and resets csv to initial state
        file_put_contents(
            $importFile,
            implode(';', ['ordernumber', 'mainnumber', 'imageUrl'])
        );
    }
}
