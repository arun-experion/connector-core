<?php

use Connector\Schema\Builder;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Connector\Schema\IntegrationSchema
 * @uses \Connector\Schema\Builder
 * @uses \Connector\Schema\Builder\RecordProperties
 * @uses \Connector\Schema\Builder\RecordProperty
 * @uses \Connector\Schema\Builder\RecordType
 * @uses \Connector\Schema\Builder\RecordTypes
 */
class IntegrationSchemaTest extends TestCase
{
    /**
     * @dataProvider IsFQNs
     */
    public function testIsFullyQualifiedName($expected, $input): void
    {
        $builder = new Builder("http://test","Test Schema");
        $contact = new Builder\RecordType("contact");
        $address = new Builder\RecordType("contact:address");

        $builder->addRecordType($contact);
        $builder->addRecordType($address);

        $schema = $builder->toSchema();
        $this->assertSame($expected, $schema->isFullyQualifiedName($input));
    }

    public static function IsFQNs(): array
    {
        return [
            "Not a FQN"                                => [false, "a"],
            "FQN with unknown record type"             => [false, "a:name"],
            "FQN with valid record type"               => [true, "contact:name"],
            "FQN with sub-record type"                 => [true, "contact:address:street"],
            "FQN with property variant"                => [true, "contact:title:abbr"],
            "FQN with sub_record and property variant" => [true, "contact:address:state:abbr"],
        ];
    }

    /**
     * @dataProvider RecordTypeFQNs
     */
    public function testGetRecordTypeFromFQN($expected, $input): void
    {
        $builder = new Builder("http://test","Test Schema");
        $contact = new Builder\RecordType("contact");
        $address = new Builder\RecordType("contact:address");

        $builder->addRecordType($contact);
        $builder->addRecordType($address);

        $schema = $builder->toSchema();
        $this->assertSame($expected, $schema->getRecordTypeFromFQN($input));
    }

    public static function RecordTypeFQNs(): array
    {
        return [
            "Not a FQN"                                => [null, "a"],
            "FQN with unknown record type"             => [null, "a:name"],
            "FQN with valid record type"               => ['contact', "contact:name"],
            "FQN with sub-record type"                 => ['contact:address', "contact:address:street"],
            "FQN with property variant"                => ['contact', "contact:title:abbr"],
            "FQN with sub_record and property variant" => ['contact:address', "contact:address:state:abbr"],
        ];
    }

    /**
     * @dataProvider RootRecordTypeFQNs
     */
    public function testGetRootRecordTypeFromFQN($expected, $input): void
    {
        $builder = new Builder("http://test","Test Schema");
        $contact = new Builder\RecordType("contact");
        $address = new Builder\RecordType("contact:address");

        $builder->addRecordType($contact);
        $builder->addRecordType($address);

        $schema = $builder->toSchema();
        $this->assertSame($expected, $schema->getRootRecordTypeFromFQN($input));
    }

    public static function RootRecordTypeFQNs(): array
    {
        return [
            "Not a FQN"                                => [null, "a"],
            "FQN with unknown record type"             => [null, "a:name"],
            "FQN with valid record type"               => ['contact', "contact:name"],
            "FQN with sub-record type"                 => ['contact', "contact:address:street"],
            "FQN with property variant"                => ['contact', "contact:title:abbr"],
            "FQN with sub_record and property variant" => ['contact', "contact:address:state:abbr"],
        ];
    }

    /**
     * @dataProvider PropertyNameFQNs
     */
    public function testPropertyNameFromFQN($expected, $input): void
    {
        $builder = new Builder("http://test","Test Schema");
        $contact = new Builder\RecordType("contact");
        $ctcaddr = new Builder\RecordType("contact:address");
        $contact->addProperty('name');
        $contact->addProperty('title');
        $contact->addProperty('address');
        $ctcaddr->addProperty('state');
        $ctcaddr->addProperty('street');
        $builder->addRecordType($contact);
        $builder->addRecordType($ctcaddr);

        $schema = $builder->toSchema();
        $this->assertSame($expected, $schema->getPropertyNameFromFQN($input));
    }

    public static function PropertyNameFQNs(): array
    {
        return [
            "Not a FQN"                                => [null, "a"],
            "FQN with unknown record type"             => [null, "a:name"],
            "FQN with unknown property"                => [null, "contact:email"],
            "FQN with known property"                  => ['name', "contact:name"],
            "FQN with record type / property overlap"  => ['street', "contact:address:street"],
            "FQN with property variant"                => ['title', "contact:title:abbr"],
        ];
    }
}
