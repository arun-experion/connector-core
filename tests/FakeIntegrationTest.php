<?php

use Connector\Mapping;
use Connector\Record\RecordLocator;
use Connector\Record\RecordKey;
use PHPUnit\Framework\TestCase;
use Connector\Integrations\Fake;

/**
 * @uses Connector\Integrations\Response
 * @uses Connector\Mapping
 * @uses Connector\Mapping\Item
 * @uses Connector\Record\RecordLocator
 * @uses Connector\Record\RecordKey
 * @uses Connector\Schema\Builder
 * @uses Connector\Schema\Builder\RecordProperties
 * @uses Connector\Schema\Builder\RecordProperty
 * @uses Connector\Schema\Builder\RecordType
 * @uses Connector\Schema\Builder\RecordTypes
 * @uses Connector\Schema\IntegrationSchema
 * @uses Connector\Record
 * @uses Connector\Record\Recordset
 * @uses Connector\Integrations\AbstractIntegration
 * @covers Connector\Integrations\Fake\Integration
 * @covers Connector\Integrations\Fake\FakeIntegrationSchema
 */
final class FakeIntegrationTest extends TestCase
{

    public function testDbOps(): void
    {
        $integration = new Fake\Integration();
        $integration->createTable("persons",  ["name text", "email text"]);
        $integration->insertRecord("persons", ["name"=>'John', "email" => 'john@example.org']);
        $integration->insertRecord("persons", ["name"=>'Jane', "email" => 'jane@example.org']);

        $record = $integration->selectRecord("persons", 1);
        $this->assertEquals(1, $record['id']);
        $this->assertEquals("John", $record['name']);
        $this->assertEquals("john@example.org", $record['email']);

        $record = $integration->selectRecord("persons", 2);
        $this->assertEquals(2, $record['id']);
        $this->assertEquals("Jane", $record['name']);
        $this->assertEquals("jane@example.org", $record['email']);
    }

    public function testSchema(): void
    {
        $integration = new Fake\Integration();
        $integration->createTable("persons", ["name text", "email text", "created datetime"]);
        $schema = $integration->discover();
        $this->assertJsonStringEqualsJsonFile(__DIR__  . "/schemas/fake.schema.json", $schema->json);
    }

    public function testExtract(): void
    {
        $integration = new Fake\Integration();
        $integration->createTable("persons",  ["name text", "email text"]);
        $integration->insertRecord("persons", ["name"=>'John', "email" => 'john@example.org']);
        $integration->insertRecord("persons", ["name"=>'Jane', "email" => 'jane@example.org']);
        $integration->discover();

        $recordLocator = new RecordLocator(["recordType" => "persons"]);
        $mapping       = new Mapping("name","email");
        $response      = $integration->extract($recordLocator, $mapping, null);

        $this->assertCount(2, $response->recordset->records);
        $this->assertEquals(["name" =>"John", "email" =>"john@example.org", 'id' => 1], $response->recordset->records[0]->data);
        $this->assertEquals(["name" =>"Jane", "email" =>"jane@example.org", "id" => 2], $response->recordset->records[1]->data);

        $this->assertEquals(['Selected 2 persons record(s)'], $integration->getLog());
    }

    public function testScopedExtract(): void
    {
        $integration = new Fake\Integration();
        $integration->createTable("persons",  ["name text", "email text"]);
        $integration->insertRecord("persons", ["name" => "John", "email" => "john@example.org"]);
        $integration->insertRecord("persons", ["name" => "Jane", "email" => "jane@example.org"]);

        $integration->createTable("applications",  ["status text", "created datetime", "persons_id integer"]);
        $integration->insertRecord("applications", ["status" => "pending", "created" => "2023-12-12 12:12:12", "persons_id" => 1]);
        $integration->insertRecord("applications", ["status" => "closed",  "created" => "2023-12-11 11:11:11", "persons_id" => 1]);
        $integration->insertRecord("applications", ["status" => "closed",  "created" => "2023-12-10 10:10:10", "persons_id" => 2]);
        $integration->discover();

        $recordLocator = new RecordLocator(["recordType" => "applications"]);
        $mapping       = new Mapping("status","created");
        $response      = $integration->extract($recordLocator, $mapping, new RecordKey(1,"persons"));

        $this->assertCount(2, $response->recordset->records);
        $this->assertEquals(["id" => 1, "status" => "pending", "created" => "2023-12-12 12:12:12"], $response->recordset->records[0]->data);
        $this->assertEquals(["id" => 2, "status" => "closed",  "created" => "2023-12-11 11:11:11"], $response->recordset->records[1]->data);
        $this->assertEquals(['Selected 2 applications record(s)'], $integration->getLog());

        $response      = $integration->extract($recordLocator, $mapping, new RecordKey(2,"persons"));
        $this->assertCount(1, $response->recordset->records);
        $this->assertEquals(["id" => 3, "status" => "closed", "created" => "2023-12-10 10:10:10"], $response->recordset->records[0]->data);

        $this->assertEquals(['Selected 1 applications record(s)'], $integration->getLog());
    }
}
