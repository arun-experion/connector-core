<?php

use Connector\Plan;
use Connector\Integrations\Fake;
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
 * @uses Connector\Integrations\AbstractIntegration
 * @uses Connector\Integrations\Fake\FakeIntegrationSchema
 * @uses Connector\Integrations\Fake\Integration
 * @uses Connector\Plan\Builder
 * @uses Connector\Plan\Builder\Operation
 * @uses Connector\Schema\Builder\RecordProperty
 * @uses Connector\Schema\Builder\RecordType
 * @covers Connector\Operation\Result
 * @covers Connector\Execution
 * @covers Connector\Operation
 */
class ExecutionTest extends TestCase
{
    /**
     *  Given a source Grades table, with full name and grade data
     *    and a single Transcript record
     *    and a target Transcript table
     * Create a Transcript record
     *
     * @throws \Connector\Exceptions\InvalidMappingException
     * @throws \Connector\Exceptions\RecordNotFound
     * @throws \Connector\Exceptions\InvalidExecutionPlan
     * @throws \Connector\Exceptions\EmptyRecordException
     */
    public function testExecution() {

        $sourceIntegration = new Fake\Integration();
        $sourceIntegration->createTable("grades", ["full_name text", "grade text"]);
        $sourceIntegration->insertRecord("grades", ["full_name"=>'Jane Doe', "grade" => 'A+']);

        $targetIntegration = new Fake\Integration();
        $targetIntegration->createTable("transcript",  ["name text", "grade text"]);

        $plan = Plan\Builder::create()->addOperation()->with()
                ->setRecordTypes('grades','transcript')
                ->mapProperty('full_name', 'name')
                ->mapProperty('grade', 'grade');

        $execution = new Execution($plan->toJSON(), $sourceIntegration, $targetIntegration);
        $execution->run();

        // 1 root node + 1 operation
        $this->assertCount(2, $execution->graph);

        $records = $targetIntegration->selectAllRecords('transcript');
        $this->assertCount(1, $records);
        $this->assertEquals('Jane Doe', $records[0]['name']);
        $this->assertEquals('A+', $records[0]['grade']);
    }

    /**
     *   Given a source Transcript table, with full name and grade data
     *     and a single Transcript record
     *     and a target Students table
     *  Create a Student record
     *     and update the Transcript record with the ID of the created Student record.
     *
     * @return void
     * @throws \Connector\Exceptions\InvalidExecutionPlan
     * @throws \Connector\Exceptions\InvalidMappingException
     * @throws \Connector\Exceptions\RecordNotFound
     * @throws \Connector\Exceptions\EmptyRecordException
     */
    public function testOperationWithResultMapping(): void
    {
        $sourceIntegration = new Fake\Integration();
        $sourceIntegration->createTable("transcript", ["studentId integer", "full_name text", "grade text"]);
        $sourceIntegration->insertRecord("transcript", ["full_name"=>'Jane Doe', "grade" => 'A+']);

        $targetIntegration = new Fake\Integration();
        $targetIntegration->createTable("students",  ["name text"]);

        $plan = Plan\Builder::create()
            ->addOperation()->with()
            ->setRecordTypes('transcript','students')
            ->mapProperty('full_name', 'name')
            ->mapResult('id', 'studentId');

        $execution = new Execution($plan->toJSON(), $sourceIntegration, $targetIntegration);
        $execution->run();

        // 1 root node + 1 operation + 1 result operation
        $this->assertCount(3, $execution->graph);

        $records = $sourceIntegration->selectAllRecords('transcript');
        $this->assertCount(1, $records);
        $this->assertEquals(1, $records[0]['studentId']);

    }

    /**
     * Given a source Transcript table, with full name and grade data
     *   and a single Transcript record
     *   and target Students and Grades tables
     * Create separate Student and Grade records.
     *
     * @return void
     * @throws \Connector\Exceptions\InvalidExecutionPlan
     * @throws \Connector\Exceptions\InvalidMappingException
     * @throws \Connector\Exceptions\RecordNotFound
     * @throws \Connector\Exceptions\EmptyRecordException
     */
    public function testMultipleOperations()
    {
        $sourceIntegration = new Fake\Integration();
        $sourceIntegration->createTable("transcript", ["full_name text", "grade text"]);
        $sourceIntegration->insertRecord("transcript", ["full_name"=>'Jane Doe', "grade" => 'A+']);

        $targetIntegration = new Fake\Integration();
        $targetIntegration->createTable("students",  ["name text"]);
        $targetIntegration->createTable("grades",  ["grade text"]);

        $plan = Plan\Builder::create()
            ->addOperation()->with()
                ->setRecordTypes('transcript','students')
                ->mapProperty('full_name', 'name')
            ->then()
            ->addOperation()->with()
                ->setRecordTypes('transcript','grades')
                ->mapProperty('grade', 'grade');

        $execution = new Execution($plan->toJSON(), $sourceIntegration, $targetIntegration);
        $execution->run();

        // 1 root node + 2 operations x 1 record extracted
        $this->assertCount(3, $execution->graph);

        $records = $targetIntegration->selectAllRecords('students');
        $this->assertCount(1, $records);
        $this->assertEquals('Jane Doe', $records[0]['name']);

        $records = $targetIntegration->selectAllRecords('grades');
        $this->assertCount(1, $records);
        $this->assertEquals('A+', $records[0]['grade']);
    }

    /**
     * Given a source Transcript table, with full name and grade data.
     *   and target Students and Grades tables
     *   and a single Transcript record
     * Create separate Student and Grade records.
     *   with the Grade record holding a reference to the Student record.
     *
     * @return void
     * @throws \Connector\Exceptions\EmptyRecordException
     * @throws \Connector\Exceptions\InvalidExecutionPlan
     * @throws \Connector\Exceptions\InvalidMappingException
     * @throws \Connector\Exceptions\RecordNotFound
     */
    public function testOperationsWithRelationship(): void
    {
        $sourceIntegration = new Fake\Integration();
        $sourceIntegration->createTable("transcript", ["studentId integer", "full_name text", "grade text"]);
        $sourceIntegration->insertRecord("transcript", ["full_name"=>'Jane Doe', "grade" => 'A+']);

        $targetIntegration = new Fake\Integration();
        $targetIntegration->createTable("students",  ["name text"]);
        $targetIntegration->createTable("grades",  ["studentId integer, grade text"]);

        $plan = Plan\Builder::create()
            ->addOperation()->with()
                ->setRecordTypes('transcript','students')
                ->mapProperty('full_name', 'name')
                ->mapResult('id', 'studentId')
            ->then()
            ->addOperation()->with()
                ->setRecordTypes('transcript','grades')
                ->mapProperty('studentId', 'studentId')
                ->mapProperty('grade', 'grade');

        $execution = new Execution($plan->toJSON(), $sourceIntegration, $targetIntegration);
        $execution->run();

        // 1 root node + (2 operations + 1 result operation) x 1 record extracted
        $this->assertCount(4, $execution->graph);

        $records = $targetIntegration->selectAllRecords('grades');
        $this->assertCount(1, $records);
        $this->assertEquals('A+', $records[0]['grade']);
        $this->assertEquals(1, $records[0]['studentId']);
    }

    /**
     *  Given a source Grades table, with full name and grade data.
     *    and multiple Grades records
     *    and a target Transcript table
     *  For each Grade record, create a Transcript record.
     *
     * @throws \Connector\Exceptions\EmptyRecordException
     * @throws \Connector\Exceptions\InvalidExecutionPlan
     * @throws \Connector\Exceptions\InvalidMappingException
     * @throws \Connector\Exceptions\RecordNotFound
     */
    public function testOperationWithMultipleRecords() {

        $sourceIntegration = new Fake\Integration();
        $sourceIntegration->createTable("grades", ["full_name text", "grade text"]);
        $sourceIntegration->insertRecord("grades", ["full_name"=>'Jane Doe', "grade" => 'A+']);
        $sourceIntegration->insertRecord("grades", ["full_name"=>'Bobby Table', "grade" => 'F']);

        $targetIntegration = new Fake\Integration();
        $targetIntegration->createTable("transcript",  ["name text", "grade text"]);

        $plan = Plan\Builder::create()->addOperation()->with()
            ->setRecordTypes('grades','transcript')
            ->mapProperty('full_name', 'name')
            ->mapProperty('grade', 'grade');

        $execution = new Execution($plan->toJSON(), $sourceIntegration, $targetIntegration);
        $execution->run();

        // 1 root node + 1 operation x 2 records extracted
        $this->assertCount(3, $execution->graph);

        $records = $targetIntegration->selectAllRecords('transcript');
        $this->assertCount(2, $records);
        $this->assertEquals('Jane Doe', $records[0]['name']);
        $this->assertEquals('A+', $records[0]['grade']);
        $this->assertEquals('Bobby Table', $records[1]['name']);
        $this->assertEquals('F', $records[1]['grade']);
    }

    /**
     * Given a source Transcript table, with full name and grade data.
     *    and target Students and Grades tables
     *  For each source record, create separate Student and Grade records.
     *    with the Grade record holding a reference to the Student record.
     *
     * @throws \Connector\Exceptions\EmptyRecordException
     * @throws \Connector\Exceptions\InvalidExecutionPlan
     * @throws \Connector\Exceptions\InvalidMappingException
     * @throws \Connector\Exceptions\RecordNotFound
     */
    public function testOperationsAndParentChildRelationships() {

        $sourceIntegration = new Fake\Integration();
        $sourceIntegration->createTable("transcript", ["studentId integer", "full_name text", "grade text"]);
        $sourceIntegration->insertRecord("transcript", ["full_name"=>'Jane Doe', "grade" => 'A+']);
        $sourceIntegration->insertRecord("transcript", ["full_name"=>'Bobby Table', "grade" => 'F']);

        $targetIntegration = new Fake\Integration();
        $targetIntegration->createTable("students",  ["name text"]);
        $targetIntegration->createTable("grades",  ["studentId integer, grade text"]);

        $plan = Plan\Builder::create()
            ->addOperation()->with()
                ->setRecordTypes('transcript','students')
                ->mapProperty('full_name', 'name')
                ->mapResult('id', 'studentId')
            ->then()
            ->addOperation()->after(1)->with()
                ->setRecordTypes('transcript','grades')
                ->mapProperty('studentId', 'studentId')
                ->mapProperty('grade', 'grade');

        $execution = new Execution($plan->toJSON(), $sourceIntegration, $targetIntegration);
        $execution->run();

        // 1 root node + (2 operations + 1 result operation) x 2 records extracted
        $this->assertCount(7, $execution->graph);

        $records = $sourceIntegration->selectAllRecords('transcript');
        $this->assertCount(2, $records);
        $this->assertEquals(1, $records[0]['studentId']);
        $this->assertEquals(2, $records[1]['studentId']);

        $records = $targetIntegration->selectAllRecords('grades');
        $this->assertCount(2, $records);
        $this->assertEquals(1, $records[0]['studentId']);
        $this->assertEquals('A+', $records[0]['grade']);
        $this->assertEquals(2, $records[1]['studentId']);
        $this->assertEquals('F', $records[1]['grade']);
    }

    /**
     * Given a source "Transcripts" table with 2 records, containing full name, course name, and grade data.
     *   and target tables for "Students", "Courses", and "Grades",
     * For each extracted Transcript record,
     *   create separate Student, Course, and Grade records,
     *   and update source Transcript record with IDs of created Student and Course records.
     *
     * @throws \Connector\Exceptions\InvalidMappingException
     * @throws \Connector\Exceptions\RecordNotFound
     * @throws \Connector\Exceptions\InvalidExecutionPlan
     * @throws \Connector\Exceptions\EmptyRecordException
     */
    public function testOperationsAndDeepParentChildRelationships() {

        /* Givens */
        $sourceIntegration = new Fake\Integration();
        $sourceIntegration->createTable("transcripts", ["studentId integer", "courseId integer", "full_name text", "course text", "grade text"]);
        $sourceIntegration->insertRecord("transcripts", ["full_name"=>'Jane Doe', "course" => "English 101", "grade" => 'A+']);
        $sourceIntegration->insertRecord("transcripts", ["full_name"=>'Bobby Table',  "course" => "Calculus 101", "grade" => 'F']);

        $targetIntegration = new Fake\Integration();
        $targetIntegration->createTable("students",  ["name text"]);
        $targetIntegration->createTable("courses",  ["name text"]);
        $targetIntegration->insertRecord("courses", ["name" => 'dummy']);
        $targetIntegration->createTable("grades",  ["studentId integer", "courseId integer", "grade text"]);

        $plan = Plan\Builder::create()
            ->addOperation()->with()
                ->setRecordTypes('transcripts','students')
                ->mapProperty('full_name', 'name')
                ->mapResult('id', 'studentId')
            ->then()
            ->addOperation()->after(1)->with()
                ->setRecordTypes('transcripts','courses')
                ->mapProperty('course', 'name')
                ->mapResult('id', 'courseId')
            ->then()
            ->addOperation()->after(2)->with()
                ->setRecordTypes('transcripts','grades')
                ->mapProperty('studentId', 'studentId')
                ->mapProperty('courseId', 'courseId')
                ->mapProperty('grade', 'grade');

        $execution = new Execution($plan->toJSON(), $sourceIntegration, $targetIntegration);
        $execution->run();

        // 1 root node + (3 operations + 2 result operation) x 2 records extracted
        $this->assertCount(11, $execution->graph);

        /*  Check that source Transcript records have been updated with IDs of created Student and Course records. */
        $records = $sourceIntegration->selectAllRecords('transcripts');
        $this->assertCount(2, $records);
        $this->assertEquals(1, $records[0]['studentId']);
        $this->assertEquals(2, $records[1]['studentId']);
        $this->assertEquals(2, $records[0]['courseId']); // Pre-existing dummy record causes new IDs to be 2 and 3.
        $this->assertEquals(3, $records[1]['courseId']);

        /* Check that Grade records includes references to created Student and Course records */
        $records = $targetIntegration->selectAllRecords('grades');
        $this->assertCount(2, $records);
        $this->assertEquals(1, $records[0]['studentId']);
        $this->assertEquals(2, $records[0]['courseId']);
        $this->assertEquals('A+', $records[0]['grade']);
        $this->assertEquals(2, $records[1]['studentId']);
        $this->assertEquals(3, $records[1]['courseId']);
        $this->assertEquals('F', $records[1]['grade']);
    }

    /**
     * Checks that the begin(), extract(), load(), and end() methods are called once each.
     *
     * @throws \Connector\Exceptions\InvalidMappingException
     * @throws \PHPUnit\Framework\MockObject\Exception
     * @throws \Connector\Exceptions\RecordNotFound
     * @throws \Connector\Exceptions\InvalidExecutionPlan
     */
    public function testInterfaceMethods()
    {
        $sourceIntegrationMock = $this->createMock(AbstractIntegration::class);
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

        $targetIntegrationMock = $this->createMock(AbstractIntegration::class);
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

    /**
     * Check that the load() is called twice if extract() returns 2 records.
     * @return void
     * @throws \Connector\Exceptions\InvalidExecutionPlan
     * @throws \Connector\Exceptions\InvalidMappingException
     * @throws \Connector\Exceptions\RecordNotFound
     * @throws \PHPUnit\Framework\MockObject\Exception
     */
    public function testInterfaceMethodsWithUnroll()
    {
        $sourceIntegrationMock = $this->createMock(AbstractIntegration::class);
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

        $targetIntegrationMock = $this->createMock(AbstractIntegration::class);
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

    /**
     * Checks that load() is called once on the source integration if a result mapping is provided.
     * @return void
     * @throws \Connector\Exceptions\InvalidExecutionPlan
     * @throws \Connector\Exceptions\InvalidMappingException
     * @throws \Connector\Exceptions\RecordNotFound
     */
    public function testInterfaceMethodsWithResultMapping()
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
            ->willReturnCallback(function($locator, $mapping){
                $expectedMapping   = new Mapping();
                $expectedMapping[] = new Item("tfa_7", "95", 'Student Rank');
                self::assertEquals($expectedMapping, $mapping);
                $recordKey   = new RecordKey("xyz");
                return (new Response())->setRecordKey($recordKey);
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
                $response->setRecordKey($recordKey);
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
