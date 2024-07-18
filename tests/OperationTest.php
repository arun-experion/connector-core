<?php

use Connector\Exceptions\EmptyRecordException;
use Connector\Exceptions\InvalidExecutionPlan;
use Connector\Exceptions\RecordNotFound;
use Connector\Exceptions\SkippedOperationException;
use Connector\Integrations\AbstractIntegration;
use Connector\Integrations\Fake;
use Connector\Integrations\Response;
use Connector\Mapping;
use Connector\Mapping\Item;
use Connector\Operation;
use Connector\Record;
use Connector\Record\RecordKey;
use Connector\Record\Recordset;
use Connector\Schema\GenericSchema;
use Connector\Schema\IntegrationSchema;
use Connector\Type\DataType;
use Connector\Type\JsonSchemaTypes;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @uses Connector\Type\DataType
 * @uses Connector\Type\TypedValue
 * @uses Connector\Operation\Result
 * @uses Connector\Schema\GenericSchema
 * @uses Connector\Schema\IntegrationSchema
 * @uses Connector\Schema\Builder\RecordProperties
 * @uses Connector\Schema\Builder\RecordTypes
 * @uses Connector\Schema\Builder\RecordProperty
 * @uses Connector\Schema\Builder\RecordType
 * @uses Connector\Schema\Builder
 * @uses Connector\Integrations\AbstractIntegration
 * @uses Connector\Integrations\Fake\FakeIntegrationSchema
 * @uses Connector\Integrations\Fake\Integration
 * @uses Connector\Localization
 * @covers Connector\Mapping
 * @covers Connector\Mapping\Item
 * @covers Connector\Record\Recordset
 * @covers Connector\Integrations\Response
 * @covers Connector\Operation
 * @covers Connector\Record
 * @covers Connector\Record\RecordKey
 * @covers Connector\Record\RecordLocator
 * @covers Connector\Operation\Formula
 * @covers Connector\Operation\Precondition
 * @covers Connector\Integrations\Database\GenericWhereClause
 */
class OperationTest extends TestCase
{

    /**
     * @param \Connector\Schema\IntegrationSchema|null $schema
     */
    private function getMockIntegration(IntegrationSchema $schema = null): MockObject
    {
        $schema = $schema ?? new GenericSchema();
        $mock = $this->getMockBuilder(AbstractIntegration::class)->getMock();
        $mock->expects($this->any())
            ->method('getSchema')
            ->willReturnCallback(function() use ($schema){ return $schema;});
        return $mock;
    }

    private function getFakeSourceIntegration(): Fake\Integration
    {
        $source = new Fake\Integration();
        $source->createTable('transcript', [
            "name text",
            "course string",
            "grade text",
            "date date",
            "time time",
            "datetime datetime",
            "credits text",
            "semester text" ]);
        $source->discover();
        return $source;
    }

    private function getFakeTargetIntegration(): Fake\Integration
    {
        $target = new Fake\Integration();
        $target->createTable('data', [
            "StudentName text",
            "Course string",
            "Grade text",
            "TestDate date",
            "Credits real",
            "Semester text" ]);
        $target->discover();
        return $target;
    }

    /**
     * @throws \Connector\Exceptions\InvalidMappingException
     * @throws \Connector\Exceptions\RecordNotFound
     * @throws \Connector\Exceptions\InvalidExecutionPlan
     * @throws \Connector\Exceptions\EmptyRecordException
     */
    public function testRun()
    {
        $source = $this->getFakeSourceIntegration();
        $target = $this->getFakeTargetIntegration();

        $source->insertRecord('transcript', ['name'=>'Jane Doe', 'grade' => 'A+']);

        $operationCfg =  [
            "recordLocators" => [
                "source" => [ "recordType" => "transcript" ],
                "target" => [ "recordType" => "data" ]
            ],
            "mapping" => [
                [ "source" => ["id" => "name"],  "target" => ["id" => "StudentName"] ],
                [ "source" => ["id" => "grade"], "target" => ["id" => "Grade"] ],
            ]
        ];

        $operation = new Operation($operationCfg, $source, $target );
        $result    = $operation->run(null, null);

        $this->assertEquals(1, $result->getLoadedRecordKey()->recordId);

        $loadedRecord = $target->selectRecord('data', $result->getLoadedRecordKey()->recordId);
        $this->assertSame(["id"          => 1,
                           "StudentName" => "Jane Doe",
                           'Course'      => null,
                           "Grade"       => "A+",
                           'TestDate'    => null,
                           'Credits'     => null,
                           'Semester'    => null,
                          ],
                          $loadedRecord);
    }

    public function testWithFullyQualifiedSourceMapping()
    {
        $source = $this->getFakeSourceIntegration();
        $target = $this->getFakeTargetIntegration();

        $source->insertRecord('transcript', ['name'=>'Jon Doe', 'grade' => 'F']);

        $operationCfg =  [
            "recordLocators" => [
                "source" => [ "recordType" => "transcript" ],
                "target" => [ "recordType" => "data" ]
            ],
            "mapping" => [
                [ "source" => ["id" => "transcript:name"],  "target" => ["id" => "StudentName"] ],
                [ "source" => ["id" => "transcript:grade"], "target" => ["id" => "Grade"] ],
            ]
        ];

        $operation = new Operation($operationCfg, $source, $target );
        $result    = $operation->run(null, null);

        $this->assertEquals(1, $result->getLoadedRecordKey()->recordId);

        $loadedRecord = $target->selectRecord('data', $result->getLoadedRecordKey()->recordId);
        $this->assertSame(["id"          => 1,
                           "StudentName" => "Jon Doe",
                           'Course'      => null,
                           "Grade"       => "F",
                           'TestDate'    => null,
                           'Credits'     => null,
                           'Semester'    => null,
                          ],
                          $loadedRecord);
    }

    public function testWithFullyQualifiedTargetMapping()
    {
        $source = $this->getFakeSourceIntegration();
        $target = $this->getFakeTargetIntegration();

        $source->insertRecord('transcript', ['name'=>'Joe Doe', 'grade' => 'C']);

        $operationCfg =  [
            "recordLocators" => [
                "source" => [ "recordType" => "transcript" ],
                "target" => [ "recordType" => "data" ]
            ],
            "mapping" => [
                [ "source" => ["id" => "name"],  "target" => ["id" => "data:StudentName"] ],
                [ "source" => ["id" => "grade"], "target" => ["id" => "data:Grade"] ],
            ]
        ];

        $operation = new Operation($operationCfg, $source, $target );
        $result    = $operation->run(null, null);

        $this->assertEquals(1, $result->getLoadedRecordKey()->recordId);

        $loadedRecord = $target->selectRecord('data', $result->getLoadedRecordKey()->recordId);
        $this->assertSame(["id"          => 1,
                           "StudentName" => "Joe Doe",
                           'Course'      => null,
                           "Grade"       => "C",
                           'TestDate'    => null,
                           'Credits'     => null,
                           'Semester'    => null,
                          ],
                          $loadedRecord);
    }


    public function testOutdatedMapping()
    {
        $source = new Fake\Integration();
        $source->createTable('transcript', [
            "name text",
            "grade text"]);
        $source->discover();
        $target = $this->getFakeTargetIntegration();
        $source->insertRecord('transcript', ['name'=>'Jane Doe', 'grade' => 'A+']);

        $operationCfg = [
            "recordLocators" => [
                "source" => [ 'recordType' => 'transcript'],
                "target" => [ 'recordType' => 'data']
            ],
            "mapping" => [
                [ "source" => ["id" => "name"],   "target" => ["id" => "StudentName"] ],
                [ "source" => ["id" => "course"], "target" => ["id" => "Course"] ],     // <- Not in source schema.
                [ "source" => ["id" => "grade"],  "target" => ["id" => "Grade"] ],
            ],
        ];

        $operation = new Operation($operationCfg, $source, $target);
        $result    = $operation->run(null, null);

        $this->assertEquals(1, $result->getLoadedRecordKey()->recordId);

        $loadedRecord = $target->selectRecord('data', $result->getLoadedRecordKey()->recordId);

        $this->assertSame(["id"          => 1,
                           "StudentName" => "Jane Doe",
                           'Course'      => '',
                           "Grade"       => "A+",
                           'TestDate'    => null,
                           'Credits'     => null,
                           'Semester'    => null,
                          ],
                          $loadedRecord);

        // Check operation log
        $this->assertEquals([
            "Selected 1 transcript record(s)",
            "Field 'course' not found in record",
            "Inserted data record, ID: 1",
                            ],
        $operation->getLog());
    }

    public function testIncompleteTargetMapping()
    {
        $source = $this->getFakeSourceIntegration();
        $target = $this->getFakeTargetIntegration();
        $source->insertRecord('transcript', ['name'=>'Jane Doe', 'grade' => 'A+']);

        $operationCfg = [
            "recordLocators" => [
                "source" => [ 'recordType' => 'transcript'],
                "target" => [ 'recordType' => 'data']
            ],
            "mapping" => [
                [ "source" => ["id" => "name"],   "target" => ["id" => "StudentName"] ],
                [ "source" => ["id" => "grade"],  "target" => [] ], // <-- incomplete mapping
            ],
        ];

        $operation = new Operation($operationCfg, $source, $target );
        $result    = $operation->run(null, null);

        $loadedRecord = $target->selectRecord('data', $result->getLoadedRecordKey()->recordId);
        $this->assertSame(["id"          => 1,
                           "StudentName" => "Jane Doe",
                           'Course'      => null,
                           "Grade"       => null,
                           'TestDate'    => null,
                           'Credits'     => null,
                           'Semester'    => null,
                          ],
                          $loadedRecord);

        // Check operation log
        $this->assertEquals([
                                'Selected 1 transcript record(s)',
                                "Incomplete mapping configuration. Target is not set.",
                                'Inserted data record, ID: 1',
                            ],
                            $operation->getLog());
    }

    public function testIncompleteSourceMapping()
    {
        $source = $this->getFakeSourceIntegration();
        $target = $this->getFakeTargetIntegration();
        $source->insertRecord('transcript', ['name'=>'Jane Doe', 'grade' => 'A+']);

        $operationCfg = [
            "recordLocators" => [
                "source" => [ 'recordType' => 'transcript'],
                "target" => [ 'recordType' => 'data']
            ],
            "mapping" => [
                [ "source" => ["id" => "name"], "target" => ["id" => "StudentName"] ],
                [ "source" => ["id" => ""],     "target" => ["id" => "Grade"] ], // <- Incomplete source mapping
            ],
        ];

        $operation = new Operation($operationCfg, $source, $target);
        $result    = $operation->run(null, null);

        $loadedRecord = $target->selectRecord('data', $result->getLoadedRecordKey()->recordId);
        $this->assertSame(["id"          => 1,
                           "StudentName" => "Jane Doe",
                           'Course'      => null,
                           "Grade"       => '',
                           'TestDate'    => null,
                           'Credits'     => null,
                           'Semester'    => null,
                          ],
                          $loadedRecord);

        // Check operation log
        $this->assertEquals([   "Selected 1 transcript record(s)",
                                "Incomplete mapping configuration. Source is not set.",
                                "Inserted data record, ID: 1"
                            ],
                            $operation->getLog());
    }

    public function testNoRecordExtracted()
    {
        $source = $this->getFakeSourceIntegration();
        $target = $this->getFakeTargetIntegration();

        $operationCfg = [
            "recordLocators" => [
                "source" => [ 'recordType' => 'transcript'],
                "target" => [ 'recordType' => 'data']
            ],
            "mapping" => [
                [ "source" => ["id" => "name"],   "target" => ["id" => "StudentName"] ],
                [ "source" => ["id" => ""],  "target" => ["id" => "Grade"] ], // <- Incomplete source mapping
            ],
        ];

        $operation = new Operation($operationCfg, $source, $target);
        $this->expectException(RecordNotFound::class);
        $operation->run(null, null);
    }

    public function testUnsupportedMappingConfiguration()
    {
        $source = $this->getFakeSourceIntegration();
        $target = $this->getFakeTargetIntegration();

        $operationCfg = [
            "recordLocators" => [
                "source" => [ 'recordType' => 'transcript'],
                "target" => [ 'recordType' => 'data']
            ],
            "mapping" => [
                // missing 'id'  attribute.
                [ "source" => ["unexpectedAttribute" => "name"],   "target" => ["id" => "StudentName"] ],
                [ "source" => ["id" => ""],  "target" => ["id" => "Grade"] ],
            ],
        ];

        $operation = new Operation($operationCfg, $source, $target);
        $this->expectException(InvalidExecutionPlan::class);
        $operation->run(null, null);
    }

    public function testSubstitution()
    {
        $source = $this->getFakeSourceIntegration();
        $target = $this->getFakeTargetIntegration();
        $source->insertRecord('transcript', ['name'=>'Jane Doe', 'semester' => 'Spring 2024']);

        $operationCfg = [
            "recordLocators" => [
                "source" => [ 'recordType' => 'transcript'],
                "target" => [ 'recordType' => 'data']
            ],
            "mapping" => [
                [ "source" => ["id" => "name"],     "target" => ["id" => "StudentName"] ],
                [ "source" => ["id" => "semester"], "target" => ["id" => "Semester"],
                  "transform" => [
                      "Fall 2023"   => "FALL '23",
                      "Spring 2024" => "SPRING '24",
                  ]
                ]
            ],
        ];

        $operation = new Operation($operationCfg, $source, $target);
        $result    = $operation->run(null, null);

        $loadedRecord = $target->selectRecord('data', $result->getLoadedRecordKey()->recordId);
        $this->assertSame(["id"          => 1,
                           "StudentName" => "Jane Doe",
                           'Course'      => null,
                           "Grade"       => null,
                           'TestDate'    => null,
                           'Credits'     => null,
                           'Semester'    => "SPRING '24",
                          ],
                          $loadedRecord);
    }

    public function testArraySubstitution()
    {
        // FakeIntegration doesn't provide array values. Use a Mock instead.
        $sourceSchema = $this->getMockBuilder(IntegrationSchema::class)->getMock();
        $sourceSchema->method('getDataType')
            ->willReturnCallback(function() {
                $d = new DataType(JsonSchemaTypes::Array);
                $d->setItems( new DataType(JsonSchemaTypes::String) );
                return $d;
            });

        $source = $this->getMockIntegration($sourceSchema);
        $source->method('extract')
            ->willReturnCallback(function(){
                $recordset   = new Recordset();
                $recordKey   = new RecordKey(1);
                $recordset[] = new Record($recordKey, ["semester" => ["Fall 2023","Spring 2024"]]);
                return (new Response())->setRecordset($recordset);
            });

        $target = $this->getFakeTargetIntegration();

        $operationCfg = [
            "recordLocators" => [
                "source" => [ 'recordType' => 'transcript'],
                "target" => [ 'recordType' => 'data']
            ],
            "mapping" => [
                [ "source" => ["id" => "semester"], "target" => ["id" => "Semester"],
                  "transform" => [
                      "Fall 2023"   => "FALL '23",
                      "Spring 2024" => "SPRING '24",
                  ]
                ]
            ],
        ];

        $operation = new Operation($operationCfg, $source, $target);
        $result    = $operation->run(null, null);

        $loadedRecord = $target->selectRecord('data', $result->getLoadedRecordKey()->recordId);
        $this->assertSame(["id"          => 1,
                           "StudentName" => null,
                           'Course'      => null,
                           "Grade"       => null,
                           'TestDate'    => null,
                           'Credits'     => null,
                           'Semester'    => "FALL '23, SPRING '24",
                          ],
                          $loadedRecord);
    }

    public function testConversion()
    {
        $source = $this->getFakeSourceIntegration();
        $target = $this->getFakeTargetIntegration();
        $source->insertRecord('transcript', ["credits"=> "1.1" ,"date" => '7/12/1974']);

        $operationCfg =  [
            "recordLocators" => [
                "source" => [ 'recordType' => 'transcript'],
                "target" => [ 'recordType' => 'data']
            ],
            "mapping" => [
                [ "source" => ["id" => "credits"], "target" => ["id" => "Credits"] ],
                [ "source" => ["id" => "date"],    "target" => ["id" => "TestDate"] ]
            ]
        ];

        $operation = new Operation($operationCfg, $source, $target);
        $result    = $operation->run(null, null);

        $loadedRecord = $target->selectRecord('data', $result->getLoadedRecordKey()->recordId);
        $this->assertSame(["id"          => 1,
                           "StudentName" => null,
                           'Course'      => null,
                           "Grade"       => null,
                           'TestDate'    => '1974-07-12',
                           'Credits'     => 1.1,
                           'Semester'    => null,
                          ],
                          $loadedRecord);
    }

    public function testMappedValue()
    {
        $source = $this->getFakeSourceIntegration();
        $target = $this->getFakeTargetIntegration();

        $operationCfg =  [
            "recordLocators" => [
                "source" => [ 'recordType' => 'transcript'],
                "target" => [ 'recordType' => 'data']
            ],
            "mapping" => [
                [ "source" => ["value" => "my value"], "target" => ["id" => "StudentName"] ]
            ]
        ];

        $operation = new Operation($operationCfg, $source, $target);
        $result    = $operation->run(null, null);

        $loadedRecord = $target->selectRecord('data', $result->getLoadedRecordKey()->recordId);
        $this->assertSame(["id"          => 1,
                           "StudentName" => "my value",
                           'Course'      => null,
                           "Grade"       => null,
                           'TestDate'    => null,
                           'Credits'     => null,
                           'Semester'    => null,
                          ],
                          $loadedRecord);
    }

    /**
     * @dataProvider MappedFormula
     */
    public function testMappedFormula($expectedMapping, $formula)
    {
        $source = $this->getFakeSourceIntegration();
        $target = $this->getFakeTargetIntegration();
        $source->insertRecord('transcript', ['name'=>'Jane Doe', 'semester' => 'Spring 2024 ']);

        $operationCfg =  [
            "recordLocators" => [
                "source" => [ 'recordType' => 'transcript'],
                "target" => [ 'recordType' => 'data']
            ],
            "mapping" => [
                [ "source" => ["formula" => $formula], "target" => ["id" => "StudentName"] ]
            ]
        ];

        $operation = new Operation($operationCfg, $source, $target);
        $result    = $operation->run(null, null);

        $loadedRecord = $target->selectRecord('data', $result->getLoadedRecordKey()->recordId);
        $this->assertSame(["id"          => 1,
                           "StudentName" => $expectedMapping,
                           'Course'      => null,
                           "Grade"       => null,
                           'TestDate'    => null,
                           'Credits'     => null,
                           'Semester'    => null,
                          ],
                          $loadedRecord);
    }

    public static function MappedFormula(): array
    {
        return [
            "Alias in Formula #1" => ["2024", "@TRIM(@RIGHT(%%semester%%, @LEN(%%semester%%)-7))",],
            "Alias in Formula #2" => ["Jane Doe Spring 2024", "@CONCAT(%%name%%,\" \",@TRIM(%%semester%%))"],
            "Standalone Alias"    => ["Spring 2024 ", "%%semester%%"],
            "No Alias"            => ["Not an alias", "Not an alias"],
        ];
    }

    /**
     * @dataProvider MappedFormulaTransform
     */
    public function testFormula($expected, $formula)
    {
        $source = $this->getFakeSourceIntegration();
        $target = $this->getFakeTargetIntegration();

        $operationCfg =  [
            "recordLocators" => [
                "source" => [ "recordType" => 'transcript'],
                "target" => [ "recordType" => 'data']
            ],
            "mapping" => [
                [ "source" => ["formula" => $formula],
                  "target" => ["id"      => "StudentName"] ]
            ]
        ];

        $operation = new Operation($operationCfg, $source, $target);
        $record    = new Record(new RecordKey("1"),
                                ["semester" => "Fall 2024",
                                    "tfa_1" => "ABC",
                                    "tfa_2" => " DEF"]);

        $result = $operation->transform($operationCfg['mapping'], $operationCfg['recordLocators'], $record);

        $this->assertEquals($expected, $result[0]['source']['value']);
    }

    public static function MappedFormulaTransform(): array
    {

        return [
                "Empty formula"             => ["",""],
                "Simple formula #1"         => ["abc","@TRIM(\" abc \")"],
                "Simple formula #2"         => ["1","@IF(1=1,1,0)"],
                "String comparison"         => ["1","@IF(\"1\"=\"1\",1,0)"],
                "Alias in Formula #1"       => ["2024", "@TRIM(@RIGHT(@SUBSTITUTE(%%semester%%,\" \",@REPT(\" \",@LEN(%%semester%%))),@LEN(%%semester%%)))"],
                "Alias in Formula #2"       => ["ABCDEF", "@CONCAT(%%tfa_1%%,@TRIM(%%tfa_2%%))"],
                "Missing parameters"        => ["#VALUE!", "@CONCAT()"],
                "Standalone Alias"          => ["Fall 2024", "%%semester%%"],
                "No Formula"                => ["Plain text", "Plain text"],
                "Not a Real Formula"        => ["@UNREAL('ABC')", "@UNREAL('ABC')"],
                "Real and fake formula mix" => ["@UNREAL(ABC)", "@UNREAL(@TRIM(\" ABC \"))"],
                "Compute custom formula"    => ["2","@COMPUTE(1+1)"],
                "Escape double quotes"      => ['"a', '@LEFT("""a""",2)'],
                "String parsing"            => [')a', '@LEFT(")a",2)'],
                "Mixed Text and Formulas"   => ["2 text ab","@COMPUTE(1+1) text @LEFT(\"abc\",2)"],
                "Invalid syntax"            => ["#VALUE!", '@IF("",""FALSE")'],
                "Invalid syntax 2"          => ['#VALUE!', '@IF("Test"="Test","TRUE","FALSE"'],
                "Date"                      => [date('m/d/Y'), '@IF(@FALSE(),"abc", @LOCALTODAY())'],

        ];
    }

    public function testFormulaInRecordLocators()
    {
        $source = $this->getFakeSourceIntegration();
        $target = $this->getFakeTargetIntegration();
        $source->insertRecord('transcript', ['name'=>'Jane Doe', 'semester' => 'Spring 2024']);

        $operationCfg =  [
            "recordLocators" => [
                "source" => [ "recordType" => '@TRIM(" transcript ")', "index" => 1 ],
                "target" => [ "recordType" => '@TRIM(" data ")', 'bad' => '@LEFT(x)']
            ],
            "mapping" => [
                [ "source" => ["id" => "name"],
                  "target" => ["id" => "StudentName"] ]
            ]
        ];

        $operation = new Operation($operationCfg, $source, $target);
        $result    = $operation->run(null, null);

        $evaluatedConfig = $operation->getConfig();
        $this->assertSame("transcript", $evaluatedConfig['recordLocators']['source']['recordType'], "Running an Operation should result in the configuration being evaluated.");
        $this->assertSame("data", $evaluatedConfig['recordLocators']['target']['recordType'], "Running an Operation should result in the configuration being evaluated.");
        $this->assertSame(1, $evaluatedConfig['recordLocators']['source']['index'], "Configuration setting that does not contain a formula should be left unchanged.");
        $this->assertSame("#NAME?", $evaluatedConfig['recordLocators']['target']['bad']);

        $this->assertSame('@TRIM(" transcript ")', $operationCfg['recordLocators']['source']['recordType'], "Running an Operation should not mutate the provided configuration");
        $this->assertSame('@TRIM(" data ")', $operationCfg['recordLocators']['target']['recordType'], "Running an Operation should not mutate the provided configuration");


        $loadedRecord = $target->selectRecord('data', $result->getLoadedRecordKey()->recordId);
        $this->assertSame(["id"          => 1,
                           "StudentName" => "Jane Doe",
                           'Course'      => null,
                           "Grade"       => null,
                           'TestDate'    => null,
                           'Credits'     => null,
                           'Semester'    => null,
                          ],
                          $loadedRecord);
    }

    public function testAliasInTargetRecordLocators()
    {
        $source = $this->getFakeSourceIntegration();
        $target = $this->getFakeTargetIntegration();
        $source->insertRecord('transcript', ['name'=>'Jane Doe', 'semester' => 'Spring 2024']);

        $operationCfg =  [
            "recordLocators" => [
                "source" => [ "recordType" => 'transcript' ],
                "target" => [ "recordType" => 'data', 'semester' => '%%semester%%']
            ],
            "mapping" => [
                [ "source" => ["id" => "name"], "target" => ["id" => "StudentName"] ]
            ]
        ];

        $operation = new Operation($operationCfg, $source, $target);
        $result    = $operation->run(null, null);

        $evaluatedConfig = $operation->getConfig();
        $this->assertSame("Spring 2024", $evaluatedConfig['recordLocators']['target']['semester']);

        $loadedRecord = $target->selectRecord('data', $result->getLoadedRecordKey()->recordId);
        $this->assertSame(["id"          => 1,
                           "StudentName" => "Jane Doe",
                           'Course'      => null,
                           "Grade"       => null,
                           'TestDate'    => null,
                           'Credits'     => null,
                           'Semester'    => null,
                          ],
                          $loadedRecord);
    }


    public function testMultiValuedFields()
    {
        $sourceSchema = $this->getMockBuilder(IntegrationSchema::class)->getMock();
        $sourceSchema->method('getDataType')
            ->willReturnCallback(function() {
                $d = new DataType(JsonSchemaTypes::Array);
                $d->setItems( new DataType(JsonSchemaTypes::Integer) );
                return $d;
            });

        $targetSchema = $this->getMockBuilder(IntegrationSchema::class)->getMock();
        $targetSchema->method('getDataType')
            ->willReturnCallback(function() {
                $d = new DataType(JsonSchemaTypes::Array);
                $d->setItems( new DataType(JsonSchemaTypes::Integer) );
                return $d;
            });

        $sourceIntegrationMock = $this->getMockIntegration($sourceSchema);
        $sourceIntegrationMock->method('extract')
            ->willReturnCallback(function(){
                $recordset   = new Recordset();
                $recordKey   = new RecordKey(1);
                $recordset[] = new Record($recordKey, ["Numbers" => [10,20]]);
                return (new Response())->setRecordset($recordset);
            });

        $targetIntegrationMock = $this->getMockIntegration($targetSchema);
        $targetIntegrationMock->method('load')
            ->willReturnCallback(function($locator, $mapping, $scope){
                $expectedMapping   = new Mapping();
                $expectedMapping[] = new Item("Numbers", [10,20]);
                self::assertSame($expectedMapping[0]->value, $mapping[0]->value);
                $key = new RecordKey(1);
                return (new Response())->setRecordKey($key);
            });

        $operationCfg =  [
            "recordLocators" => [
                "source" => [ "recordType" => '' ],
                "target" => [ "recordType" => '' ]
            ],
            "mapping" => [
                [ "source" => ["id" => "Numbers"],  "target" => ["id" => "Numbers"] ]
            ]
        ];

        $operation = new Operation($operationCfg, $sourceIntegrationMock, $targetIntegrationMock );
        $operation->run(null, null);
    }

    /**
     * @covers \Connector\Integrations\Document\PlainText
     * @covers \Connector\Integrations\Document\AbstractDocument
     * @uses \Connector\Graph
     * @uses \Connector\Execution
     */
    public function testDocumentMapping()
    {
        $source = $this->getFakeSourceIntegration();

        $target = new Fake\Integration();
        $target->createTable('data', ["Transcript text"]);
        $target->discover();

        $source->insertRecord('transcript', ['name'=>'Jane Doe', 'grade' => 'A+']);

        $template = "{% begin transcript %}Student: %%name%% - Grade: %%grade%%{% end transcript %}";

        $operationCfg =  [
            "recordLocators" => [
                "source" => [ "recordType" => "transcript" ],
                "target" => [ "recordType" => "data" ]
            ],
            "mapping" => [
                [ "source" => ["document" => [ "template" => $template, "format" => "plain-text"]],
                  "target" => ["id" => "Transcript"] ],
            ]
        ];

        $operation = new Operation($operationCfg, $source, $target );
        $result    = $operation->run(null, null);

        $this->assertEquals(1, $result->getLoadedRecordKey()->recordId);

        $loadedRecord = $target->selectRecord('data', $result->getLoadedRecordKey()->recordId);
        $this->assertSame(["id"         => 1,
                           "Transcript" => "Student: Jane Doe - Grade: A+"
                          ], $loadedRecord);
    }

    /**
     * @covers \Connector\Integrations\Document\PlainText
     * @covers \Connector\Integrations\Document\AbstractDocument
     * @uses \Connector\Graph
     * @uses \Connector\Execution
     */
    public function testUnscopedDocument()
    {
        $source = new Fake\Integration();
        $source->createTable('student', [ "name text", "email string"]);
        $source->createTable('grade',  [ "student_id integer", "grade text", "course text" ]);
        $source->discover();

        $target = new Fake\Integration();
        $target->createTable('data', ["Transcript text"]);
        $target->discover();

        $source->insertRecord('student', ['name'=>'Jane Doe']);
        $source->insertRecord('student', ['name'=>'John Smith']);
        $source->insertRecord('grade', ['student_id'=> 1, 'grade' => 'A+', 'course' => 'English 101']);
        $source->insertRecord('grade', ['student_id'=> 2, 'grade' => 'B', 'course' => 'Calculus 101']);

        $template = "{% begin student %}Student: %%name%% - {% begin grade %}Grade: %%grade%% {% end grade %} {% end student %}";

        $operationCfg =  [
            "recordLocators" => [
                "source" => [ "recordType" => "student" ],
                "target" => [ "recordType" => "data" ]
            ],
            "mapping" => [
                [ "source" => ["document" => [ "template" => $template, "format" => "plain-text"]],
                  "target" => ["id" => "Transcript"] ],
            ]
        ];

        $operation = new Operation($operationCfg, $source, $target );
        $result    = $operation->run(null, null);

        $this->assertEquals(1, $result->getLoadedRecordKey()->recordId);

        $loadedRecord = $target->selectRecord('data', $result->getLoadedRecordKey()->recordId);
        $this->assertSame(["id"         => 1,
                           "Transcript" => "Student: Jane Doe - Grade: A+  Student: John Smith - Grade: B  "
                          ], $loadedRecord);
    }

    /**
     * @covers \Connector\Integrations\Document\PlainText
     * @covers \Connector\Integrations\Document\AbstractDocument
     * @uses \Connector\Graph
     * @uses \Connector\Execution
     */
    public function testScopedDocument()
    {
        $source = new Fake\Integration();
        $source->createTable('student', [ "name text", "email string"]);
        $source->createTable('grade',   [ "student_id integer", "grade text", "course text" ]);
        $source->discover();

        $target = new Fake\Integration();
        $target->createTable('data', ["Transcript text"]);
        $target->discover();

        $source->insertRecord('student', ['name' => 'Jane Doe']);
        $source->insertRecord('student', ['name' => 'John Smith']);
        $source->insertRecord('grade', ['student_id' => 1, 'grade' => 'A+', 'course' => 'English 101']);
        $source->insertRecord('grade', ['student_id' => 2, 'grade' => 'B',  'course' => 'Calculus 101']);

        $template = "{% begin student %}Student: %%name%% - {% begin grade %}Grade: %%grade%% {% end grade %} {% end student %}";

        $operationCfg =  [
            "recordLocators" => [
                "source" => [ "recordType" => "student" ],
                "target" => [ "recordType" => "data" ]
            ],
            "mapping" => [
                [ "source" => ["document" => [ "template" => $template, "format" => "plain-text"]],
                  "target" => ["id" => "Transcript"] ],
            ]
        ];

        $operation = new Operation($operationCfg, $source, $target );
        $result    = $operation->run(new RecordKey(2, "student"), null);

        $this->assertEquals(1, $result->getLoadedRecordKey()->recordId);

        $loadedRecord = $target->selectRecord('data', $result->getLoadedRecordKey()->recordId);
        $this->assertSame(["id"         => 1,
                           "Transcript" => "Student: John Smith - Grade: B  "
                          ], $loadedRecord);
    }
}
