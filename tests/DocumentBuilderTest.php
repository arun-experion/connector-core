<?php

use Connector\Integrations\Fake;
use Connector\Integrations\Document\PlainText as PlainTextDocument;
use PHPUnit\Framework\TestCase;

/**
 * @covers Connector\Integrations\Document\PlainText
 * @uses Connector\Execution
 * @uses Connector\Graph
 * @uses Connector\Integrations\AbstractIntegration
 * @uses Connector\Integrations\Document\AbstractDocument
 * @uses Connector\Integrations\Fake\FakeIntegrationSchema
 * @uses Connector\Integrations\Fake\Integration
 * @uses Connector\Integrations\Response
 * @uses Connector\Localization
 * @uses Connector\Mapping
 * @uses Connector\Mapping\Item
 * @uses Connector\Operation
 * @uses Connector\Operation\Result
 * @uses Connector\Record
 * @uses Connector\Record\RecordKey
 * @uses Connector\Record\RecordLocator
 * @uses Connector\Record\Recordset
 * @uses Connector\Schema\Builder
 * @uses Connector\Schema\Builder\RecordProperties
 * @uses Connector\Schema\Builder\RecordProperty
 * @uses Connector\Schema\Builder\RecordType
 * @uses Connector\Schema\Builder\RecordTypes
 * @uses Connector\Schema\GenericSchema
 * @uses Connector\Schema\IntegrationSchema
 * @uses Connector\Type\DataType
 * @uses Connector\Type\TypedValue
 */
class DocumentBuilderTest extends TestCase
{
    public function testDocumentBuilder(): void
    {
        $template = "{% begin person %}Name: %%name%% Email: %%email%% {% end person %}";

        $source = new Fake\Integration();
        $source->createTable('person', ["name text", "email text" ]);
        $source->insertRecord('person',['name'=>'John', 'email' => 'john@example.org']);
        $source->discover();

        $document = new PlainTextDocument();
        $document->setTemplate($template);

        $execution = $document->buildPlan($source);
        $execution->run();

        $text = $document->getDocument();

        $this->assertEquals("Name: John Email: john@example.org ", $text);
    }

    public function testDocumentBuilderWithRepeatedContent(): void
    {
        $template = "{% begin person %}Name: %%name%% Email: %%email%% {% end person %}";

        $source   = new Fake\Integration();
        $source->createTable('person', ["name text", "email text" ]);
        $source->insertRecord('person',['name'=>'John', 'email' => 'john@example.org']);
        $source->insertRecord('person',['name'=>'Jane', 'email' => 'jane@example.org']);
        $source->discover();

        $document = new PlainTextDocument();
        $document->setTemplate($template);

        $execution = $document->buildPlan($source);
        $execution->run();

        $text = $document->getDocument();

        $this->assertEquals("Name: John Email: john@example.org Name: Jane Email: jane@example.org ", $text);
    }

    public function testDocumentBuilderWithNestedRepeatedContent(): void
    {
        $template = "{% begin person %}Name: %%name%% {% begin info %}Email: %%email%% {% end info %}{% end person %}";

        $source   = new Fake\Integration();
        $source->createTable('person',  ["name text" ]);
        $source->insertRecord('person', ["name" => "John"]);
        $source->insertRecord('person', ["name" => "Jane"]);

        $source->createTable('info',    ["email text", "person_id int" ]);
        $source->insertRecord('info',   ["email" => "john@example.org", "person_id" => 1]);
        $source->insertRecord('info',   ["email" => "jane@example.org", "person_id" => 2]);

        $source->discover();

        $document = new PlainTextDocument();
        $document->setTemplate($template);

        $execution = $document->buildPlan($source);
        $execution->run();

        $text = $document->getDocument();

        $this->assertEquals("Name: John Email: john@example.org Name: Jane Email: jane@example.org ", $text);
    }

    public function testDocumentBuilderWithMultiLineTemplate(): void
    {
        $template = <<<TEXT
{% begin Workflow %}{
  "first_name":"%%tfa_1%%",
  "last_name":"%%tfa_2%%"
}{% end Workflow %}
TEXT;

        $source   = new Fake\Integration();
        $source->createTable('Workflow',  ["tfa_1 text", "tfa_2 text"]);
        $source->insertRecord('Workflow', ["tfa_1" => "John", "tfa_2" => "Doe"]);
        $source->discover();

        $document = new PlainTextDocument();
        $document->setTemplate($template);

        $execution = $document->buildPlan($source);
        $execution->run();

        $text = $document->getDocument();

        $expected = <<<TEXT
{
  "first_name":"John",
  "last_name":"Doe"
}
TEXT;
        $this->assertEquals($expected, $text);

    }
}
