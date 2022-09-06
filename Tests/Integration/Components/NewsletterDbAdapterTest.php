<?php
declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport\Tests\Integration\Components;

use SwagImportExport\Components\DbAdapters\DataDbAdapter;
use SwagImportExport\Tests\Helper\DataProvider\ProfileDataProvider;
use SwagImportExport\Tests\Helper\DbAdapterTestHelper;

class NewsletterDbAdapterTest extends DbAdapterTestHelper
{
    protected string $yamlFile = 'TestCases/newslettersDbAdapter.yml';

    public function setUp(): void
    {
        parent::setUp();

        $this->dbAdapter = DataDbAdapter::NEWSLETTER_RECIPIENTS_ADAPTER;
        $this->dbTable = ProfileDataProvider::NEWSLETTER_TABLE;
    }

    /**
     * @dataProvider writeProvider
     *
     * @param array<string, mixed> $data
     */
    public function testWrite(array $data, int $expectedInsertedRows): void
    {
        $this->write($data, $expectedInsertedRows);
    }

    public function writeProvider(): array
    {
        return $this->getDataProvider('testWrite');
    }
}
