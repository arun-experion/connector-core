<?php


use Connector\Execution;
use Connector\Integrations\AbstractIntegration;
use Connector\Integrations\Response;
use Connector\Mapping;
use Connector\Mapping\Item;
use Connector\Record;
use Connector\Record\RecordKey;
use Connector\Record\Recordset;
use Connector\Schema\GenericSchema;
use PHPUnit\Framework\TestCase;

/**
 * @uses Connector\Graph
 * @uses Connector\Integrations\Response
 * @uses Connector\Mapping
 * @uses Connector\Mapping\Item
 * @uses Connector\Record
 * @uses Connector\Record\RecordKey
 * @uses Connector\Record\RecordLocator
 * @uses Connector\Record\Recordset
 * @uses Connector\Schema\GenericSchema
 * @uses Connector\Schema\IntegrationSchema
 * @uses Connector\Type\DataType
 * @uses Connector\Type\TypedValue
 * @uses Connector\Schema\Builder\RecordProperties
 * @uses Connector\Schema\Builder\RecordTypes
 * @uses Connector\Schema\Builder
 * @uses Connector\Localization
 * @covers Connector\Operation\Result
 * @covers Connector\Execution
 * @covers Connector\Operation
 */
class ExecutionTest extends TestCase
{
    public function testExecution()
    {
        $sourceIntegrationMock = $this->getMockBuilder(AbstractIntegration::class)->getMock();
        $sourceIntegrationMock
            ->expects($this->any())
            ->method('getSchema')
            ->willReturnCallback(function() { return new GenericSchema(); });

        $sourceIntegrationMock
            ->expects($this->once())
            ->method('extract')
            ->willReturnCallback(function(){
                $recordset   = new Recordset();
                $recordKey   = new RecordKey(1);
                $recordset[] = new Record($recordKey, ["tfa_1" => "Jane Doe", "tfa_6" => "A+"]);
                return (new Response())->setRecordset($recordset);
            });

        $targetIntegrationMock = $this->getMockBuilder(AbstractIntegration::class)->getMock();
        $targetIntegrationMock
            ->expects($this->any())
            ->method('getSchema')
            ->willReturnCallback(function() { return new GenericSchema(); });
        $targetIntegrationMock
            ->expects($this->once())
            ->method('load')
            ->willReturnCallback(function(){
                return (new Response())->setRecordKey(new RecordKey(100));
            });
        $targetIntegrationMock
            ->expects($this->once())
            ->method('begin');
        $targetIntegrationMock
            ->expects($this->once())
            ->method('end');

        $plan = file_get_contents(__DIR__."/fixtures/plan.json");
        $execution = new Execution($plan, $sourceIntegrationMock, $targetIntegrationMock);
        $execution->run();
    }


    public function testExecutionWithUnroll()
    {

        $sourceIntegrationMock = $this->getMockBuilder(AbstractIntegration::class)->getMock();
        $sourceIntegrationMock
            ->expects($this->any())
            ->method('getSchema')
            ->willReturnCallback(function() { return new GenericSchema(); });

        $sourceIntegrationMock
            ->expects($this->once())
            ->method('extract')
            ->willReturnCallback(function(){
                $recordset   = new Recordset();
                $recordKey   = new RecordKey(1);
                $recordset[] = new Record($recordKey, ["tfa_1" => "Jane Doe",  "tfa_6" => "A+"]);
                $recordset[] = new Record($recordKey, ["tfa_1" => "Bob Smith", "tfa_6" => "B-"]); // <- extra record
                return (new Response())->setRecordset($recordset);
            });

        $targetIntegrationMock = $this->getMockBuilder(AbstractIntegration::class)->getMock();
        $targetIntegrationMock
            ->expects($this->any())
            ->method('getSchema')
            ->willReturnCallback(function() { return new GenericSchema(); });
        $targetIntegrationMock
            ->expects($this->exactly(2))
            ->method('load')
            ->willReturnCallback(function(){
                return (new Response())->setRecordKey(new RecordKey(100));
            });
        $targetIntegrationMock
            ->expects($this->once())
            ->method('begin');
        $targetIntegrationMock
            ->expects($this->once())
            ->method('end');

        $plan = file_get_contents(__DIR__."/fixtures/plan.json");
        $execution = new Execution($plan, $sourceIntegrationMock, $targetIntegrationMock);
        $execution->run();
    }

    public function testExecutionWithResultMapping()
    {
        $sourceIntegrationMock = $this->getMockBuilder(AbstractIntegration::class)->getMock();
        $sourceIntegrationMock
            ->expects($this->any())
            ->method('getSchema')
            ->willReturnCallback(function() { return new GenericSchema(); });

        $sourceIntegrationMock
            ->expects($this->once())
            ->method('extract')
            ->willReturnCallback(function(){
                $recordset   = new Recordset();
                $recordKey   = new RecordKey(1);
                $recordset[] = new Record($recordKey, ["tfa_1" => "Jane Doe",  "tfa_6" => "A+"]);
                return (new Response())->setRecordset($recordset);
            });

        $sourceIntegrationMock
            ->expects($this->once())
            ->method('load')
            ->willReturnCallback(function($locator, $mapping, $scope){
                $expectedMapping   = new Mapping();
                $expectedMapping[] = new Item("tfa_7", "95", 'Student Rank');
                self::assertEquals($expectedMapping, $mapping);
                return new Response();
            });

        $targetIntegrationMock = $this->getMockBuilder(AbstractIntegration::class)->getMock();
        $targetIntegrationMock
            ->expects($this->any())
            ->method('getSchema')
            ->willReturnCallback(function() { return new GenericSchema(); });

        $targetIntegrationMock
            ->expects($this->once())
            ->method('load')
            ->willReturnCallback(function(){
                $response    = new Response();
                $recordset   = new Recordset();
                $recordKey   = new RecordKey(1);
                $recordset[] = new Record($recordKey, ["rank" => "95"]);
                $response->setRecordset($recordset);
                return $response;
            });
        $targetIntegrationMock
            ->expects($this->once())
            ->method('begin');
        $targetIntegrationMock
            ->expects($this->once())
            ->method('end');

        $targetIntegrationMock
            ->expects($this->never())
            ->method('extract');

        $plan = file_get_contents(__DIR__."/fixtures/plan.json");
        $execution = new Execution($plan, $sourceIntegrationMock, $targetIntegrationMock);
        $execution->run();
    }
}
