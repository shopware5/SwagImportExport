<?php
declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport\Tests\Unit\Components\Converters;

use PHPUnit\Framework\TestCase;
use SwagImportExport\Components\Converter\XmlConverter;

class XmlConverterTest extends TestCase
{
    private XmlConverter $xmlConverter;

    protected function setUp(): void
    {
        parent::setUp();
        $this->xmlConverter = new XmlConverter();
    }

    public function testEncodeWithEmptyInputArray(): void
    {
        $emptyArray = [];
        $result = $this->xmlConverter->_encode($emptyArray);
        static::assertEmpty($result, 'Empty input should return an empty string.');
    }

    public function testEncodeRootElement(): void
    {
        $expectedElementContent = "<root></root>\r\n";

        $inputArray = [
            'root' => [],
        ];

        $result = $this->xmlConverter->_encode($inputArray);

        static::assertEquals($expectedElementContent, $result, 'Expected empty element content, but root element contains elements.');
    }

    public function testEncodeWithCustomRootElementName(): void
    {
        $customRootElement = 'customRoot';

        $expectedElementContent = <<<EOD
    <customRoot>\r
\t<child>child value</child>\r
</customRoot>\r
EOD;

        $transformArrayTree = [
            'root' => [
                'child' => 'child value',
            ],
        ];

        $result = $this->xmlConverter->_encode($transformArrayTree, 0, $customRootElement);
        static::assertXmlStringEqualsXmlString($expectedElementContent, $result, 'Setting a custom root element name failed.');
    }

    public function testEncodeWithChildElements(): void
    {
        $expectedXml = <<<EOD
<root>
	<child1>
		<child1.1>child 1.1 value</child1.1>
	</child1>
	<child2>
		<child2.1>child 2.1 value</child2.1>
		<child2.2>child 2.2 value</child2.2>
	</child2>
	<child3>child 3 value</child3>
</root>
EOD;

        $transformArray = [
            'root' => [
                'child1' => [
                    'child1.1' => 'child 1.1 value',
                ],
                'child2' => [
                    'child2.1' => 'child 2.1 value',
                    'child2.2' => 'child 2.2 value',
                ],
                'child3' => 'child 3 value',
            ],
        ];

        $result = $this->xmlConverter->_encode($transformArray);

        static::assertXmlStringEqualsXmlString($expectedXml, $result, 'XML-child elements does not match.');
    }

    public function testEncodeAttributes(): void
    {
        $expectedXml = <<<EOD
<elementWithAttributes attribute1="attr1 value" attribute3="attr3 value">element value</elementWithAttributes>
EOD;

        $transformArray = [
            'elementWithAttributes' => [
                '_attributes' => [
                    'attribute1' => 'attr1 value',
                    'attribute3' => 'attr3 value',
                ],
                '_value' => 'element value',
            ],
        ];

        $result = $this->xmlConverter->_encode($transformArray);
        static::assertXmlStringEqualsXmlString($expectedXml, $result, "Failed asserting that xml element has attributes, i.e. <element attr1='value' />");
    }

    public function testEncodeWithEmptyValueAndAttributeShouldReturnEmptyElement(): void
    {
        $expectedXml = <<<EOD
<elementWithInvalidAttributeValue attribute1="attr1 value"/>
EOD;

        $transformArray = [
            'elementWithInvalidAttributeValue' => [
                '_attributes' => [
                    'attribute1' => 'attr1 value',
                ],
            ],
        ];

        $result = $this->xmlConverter->_encode($transformArray);

        static::assertXmlStringEqualsXmlString($expectedXml, $result, 'Failed asserting that empty elements will be written with attributes.');
    }

    public function testEncodeWithSpecialCharsShouldAddCDATASection(): void
    {
        $expectedXml = <<<EOD
<root>
	<text_with_special_chars><![CDATA[<p>This is my <strong>Text</strong> which has a lot of !special! &&chars&&!</p>]]></text_with_special_chars>
</root>
EOD;

        $transformArray = [
            'root' => [
                'text_with_special_chars' => '<p>This is my <strong>Text</strong> which has a lot of !special! &&chars&&!</p>',
            ],
        ];

        $result = $this->xmlConverter->_encode($transformArray);
        static::assertXmlStringEqualsXmlString($expectedXml, $result, 'Failed adding CDATA-section to content if special characters will be used.');
    }

    public function testEncodeElementWithOneToManyAssociation(): void
    {
        $expectedXml = <<<EOD
<root>
  <oneToManyElement>
    <firstAssociation>
      <child3>child 3 value</child3>
    </firstAssociation>
  </oneToManyElement>
  <oneToManyElement>
    <secondAssociation>
      <child3>child 3 value</child3>
    </secondAssociation>
  </oneToManyElement>
  <oneToManyElement>
    <thirdAssociation>
      <child3>child 3 value</child3>
    </thirdAssociation>
  </oneToManyElement>
</root>
EOD;

        $transformArray = [
            'root' => [
                'oneToManyElement' => [
                    1 => [
                        'firstAssociation' => [
                            'child3' => 'child 3 value',
                        ],
                    ],
                    2 => [
                        'secondAssociation' => [
                            'child3' => 'child 3 value',
                        ],
                    ],
                    3 => [
                        'thirdAssociation' => [
                            'child3' => 'child 3 value',
                        ],
                    ],
                ],
            ],
        ];

        $result = $this->xmlConverter->_encode($transformArray);
        static::assertXmlStringEqualsXmlString($expectedXml, $result, 'Failed creating multiple elements by having one-to-many associations via numeric indexed arrays.');
    }

    public function testEncodeWithBooleanValues(): void
    {
        $expectedXml = <<<EOD
<root>
	<child_false>0</child_false>
	<child_true>1</child_true>
	<child_0>0</child_0>
	<child_1>1</child_1>
</root>
EOD;

        $transformArray = [
            'root' => [
                'child_false' => false,
                'child_true' => true,
                'child_0' => 0,
                'child_1' => 1,
            ],
        ];

        $result = $this->xmlConverter->_encode($transformArray);

        static::assertXmlStringEqualsXmlString($expectedXml, $result);
    }
}
