<?php

namespace Tests\Shopware\ImportExport;

use Shopware\Components\SwagImportExport\Profile;
use Shopware\Components\SwagImportExport\DataWorkflow;
use Tests\Shopware\ImportExport\ImportExportTestHelper;

class DataWorkflowTest extends ImportExportTestHelper
{

    protected function getDataSet()
    {
        return new \PHPUnit_Extensions_Database_DataSet_YamlDataSet(
                dirname(__FILE__) . "/test.yml"
        );
    }

    public function testExportWriter()
    {
        $postData = array(
            'profileId' => 1,
            'type' => 'export',
            'format' => 'xml',
            'sessionId' => 1125,
            'fileName' => 'test.xml'
        );

        $profile = $this->Plugin()->getProfileFactory()->loadProfile($postData);

        $dataFactory = $this->Plugin()->getDataFactory();

        $dbAdapter = $dataFactory->createDbAdapter($profile->getType());
        $dataSession = $dataFactory->loadSession($postData);

        //create dataIO
        $dataIO = $dataFactory->createDataIO($dbAdapter, $dataSession);

        $colOpts = $dataFactory->createColOpts($postData['columnOptions']);
        $limit = $dataFactory->createLimit($postData['limit']);
        $filter = $dataFactory->createFilter($postData['filter']);
        $maxRecordCount = $postData['max_record_count'];
        $type = $postData['type'];
        $format = $postData['format'];

        $dataIO->initialize($colOpts, $limit, $filter, $type, $format, $maxRecordCount);

        $fileWriter = $this->getMockBuilder('\Shopware\Components\SwagImportExport\FileIO\XmlFileWriter')
                ->disableOriginalConstructor()
                ->getMock();

        // Configure file writer.
        $fileWriter->expects($this->any())
                ->method('writeHeader');
        $fileWriter->expects($this->exactly(2))
                ->method('writeRecords');
        $fileWriter->expects($this->any())
                ->method('writeFooter');

        $dataTransformerChain = $this->Plugin()->getDataTransformerFactory()->createDataTransformerChain(
                $profile, array('isTree' => $fileWriter->hasTreeStructure())
        );

        $dataWorkflow = new DataWorkflow($dataIO, $profile, $dataTransformerChain, $fileWriter);
        
        echo '<pre>';
        print_r($postData);
        echo '</pre>';
        $postData = $dataWorkflow->export($postData);
        echo '<pre>';
        print_r($postData);
        echo '</pre>';
        $postData = $dataWorkflow->export($postData);
        echo '<pre>';
        print_r($postData);
        echo '</pre>';
    }

    public function testImportLifeCycle()
    {
        
    }

}
