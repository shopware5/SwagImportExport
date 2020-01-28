<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport\Tests\Unit\Components\SwagImportExport\Validator;

use PHPUnit\Framework\TestCase;
use Shopware\Components\SwagImportExport\Utils\DbAdapterHelper;

class DbAdapterHelperTest extends TestCase
{
    /**
     * @var DbAdapterHelper
     */
    private $SUT;

    protected function setUp(): void
    {
        parent::setUp();
        $this->SUT = new DbAdapterHelper();
    }

    public function test_decode_html_entities()
    {
        $inputRecords = [
            [
                'integer' => 100,
                'float' => 1.5,
                'textWithHtml' => '&lt;b&gt;With bold text with html entities&lt;/b&gt;',
                'false' => false,
                'true' => true,
                'string' => 'Hi, this is a string',
            ],
        ];

        $result = DbAdapterHelper::decodeHtmlEntities($inputRecords);

        static::assertEquals('100', $result[0]['integer'], 'Could not decode integer');
        static::assertEquals('1.5', $result[0]['float'], 'Could not decode float');
        static::assertEquals('<b>With bold text with html entities</b>', $result[0]['textWithHtml'], 'Could not decode string with html tags');
        static::assertEquals('0', $result[0]['false'], 'Could not decode boolean false');
        static::assertEquals('1', $result[0]['true'], 'Could not decode boolean true');
        static::assertEquals('Hi, this is a string', $result[0]['string'], 'Could not decode a string');
    }
}
