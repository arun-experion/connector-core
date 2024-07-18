<?php

use Connector\Exceptions\InvalidSchemaException;
use Connector\Operation;
use Connector\Plan;
use Connector\Integrations\Fake;
use Connector\Execution;
use PHPUnit\Framework\TestCase;


/**
 * @covers \Connector\Execution
 * @uses Connector\Graph
 * @uses Connector\Integrations\AbstractIntegration
 * @uses Connector\Integrations\Fake\FakeIntegrationSchema
 * @uses Connector\Integrations\Fake\Integration
 * @uses Connector\Integrations\Response
 * @uses Connector\Localization
 * @uses Connector\Mapping
 * @uses Connector\Mapping\Item
 * @uses Connector\Operation
 * @uses Connector\Operation\Formula
 * @uses Connector\Operation\Result
 * @uses Connector\Plan\Builder
 * @uses Connector\Plan\Builder\Operation
 * @uses Connector\Record
 * @uses Connector\Record\RecordKey
 * @uses Connector\Record\RecordLocator
 * @uses Connector\Record\Recordset
 * @uses Connector\Schema\Builder
 * @uses Connector\Schema\Builder\RecordProperties
 * @uses Connector\Schema\Builder\RecordProperty
 * @uses Connector\Schema\Builder\RecordType
 * @uses Connector\Schema\Builder\RecordTypes
 * @uses Connector\Schema\IntegrationSchema
 * @uses Connector\Type\DataType
 * @uses Connector\Type\TypedValue
 */
class ErrorHandlingTest extends TestCase
{
    private Fake\Integration $sourceIntegration;
    private Fake\Integration $targetIntegration;

    protected function setUp(): void
    {
        $this->sourceIntegration = new Fake\Integration();
        $this->sourceIntegration->createTable("grades", ["full_name text", "grade text"]);
        $this->sourceIntegration->insertRecord("grades", ["full_name"=>'Jane Doe', "grade" => 'A+']);

        $this->targetIntegration = new Fake\Integration();
        $this->targetIntegration->createTable("transcript",  ["name text", "grade text"]);

        parent::setUp();
    }

    public function testExecutionThrowsException() {

        $plan = Plan\Builder::create()->addOperation()->with()
                ->setRecordTypes('grades','UnknownTable')
                ->mapProperty('full_name', 'name')
                ->mapProperty('grade', 'grade');

        $execution = new Execution($plan->toJSON(), $this->sourceIntegration, $this->targetIntegration);
        $this->expectException(InvalidSchemaException::class);
        $execution->run();
    }

    public function testOperationThrowsException() {

        $builder = Plan\Builder::create();
        $operationCfg = new Plan\Builder\Operation($builder);
        $operationCfg->setRecordTypes('grades','UnknownTable')
            ->mapProperty('full_name', 'name')
            ->mapProperty('grade', 'grade');

        $operation = new Operation($operationCfg->config, $this->sourceIntegration, $this->targetIntegration);
        $this->expectException(InvalidSchemaException::class);
        $operation->run(null,null);
    }


    public function testIntegrationLogIsAvailableAfterException() {

        $plan = Plan\Builder::create()->addOperation()->with()
            ->setRecordTypes('grades','UnknownTable')
            ->mapProperty('full_name', 'name')
            ->mapProperty('grade', 'grade');

        $execution = new Execution($plan->toJSON(), $this->sourceIntegration, $this->targetIntegration);

        try {
            $execution->run();
        } catch (InvalidSchemaException $exception) { }

        $this->assertEquals([ 1 => ['Selected 1 grades record(s)']], $execution->getLog());
    }


}
