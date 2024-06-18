<?php

use Connector\Mapping;
use Connector\Mapping\Item;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Connector\Mapping
 * @covers \Connector\Mapping\Item
 */
final class MappingTest extends TestCase
{

    function testSetMappingAsArray(): void
    {
        $m   = new Mapping();
        $m[] = new Item('abc','1234','some label');
        $this->assertTrue($m->hasItem('abc'));
        $this->assertEquals(["1234"], $m->getValuesByKey('abc'));
        $this->assertInstanceOf(Item::class, $m->getItemsByKey('abc')[0]);
        $this->assertEquals("1234", $m->getItemsByKey('abc')[0]->value);
    }

    function testSetMappingWithConstructor(): void
    {
        $m   = new Mapping( new Item('abc','1234','some label') );
        $this->assertTrue($m->hasItem('abc'));
        $this->assertEquals(["1234"], $m->getValuesByKey('abc'));
        $this->assertInstanceOf(Item::class, $m->getItemsByKey('abc')[0]);
        $this->assertEquals("1234", $m->getItemsByKey('abc')[0]->value);

        $m   = new Mapping(["abc" => "1234", "def"=>"5678"]);
        $this->assertTrue($m->hasItem('abc'));
        $this->assertTrue($m->hasItem('def'));
        $this->assertEquals(["1234"], $m->getValuesByKey('abc'));
        $this->assertInstanceOf(Item::class, $m->getItemsByKey('abc')[0]);
        $this->assertEquals("1234", $m->getItemsByKey('abc')[0]->value);

        $m   = new Mapping(["abc", "def"]);
        $this->assertTrue($m->hasItem(0));
        $this->assertEquals(["abc"], $m->getValuesByKey(0));
        $this->assertInstanceOf(Item::class, $m->getItemsByKey(0)[0]);
        $this->assertEquals("abc", $m->getItemsByKey(0)[0]->value);

    }

}
