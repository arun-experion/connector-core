<?php

use Connector\Exceptions\InvalidExecutionPlan;
use Connector\Graph;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Connector\Graph
 */
final class GraphTest extends TestCase
{

    public function testJSONParse(): void
    {
        $plan  = file_get_contents(__DIR__ ."/fixtures/plan.json");
        $graph = new Graph($plan);
        $this->assertIsArray($graph->graph);
    }

    public function testGetNodeById(): void
    {
        $plan  = file_get_contents(__DIR__ ."/fixtures/plan.json");
        $graph = new Graph($plan);
        $node  = $graph->getNodeById(1);

        $this->assertIsArray($node);
        $this->assertArrayHasKey('id',$node);
        $this->assertArrayHasKey('in',$node);
        $this->assertArrayHasKey('out',$node);
        $this->assertEquals(1, $node['id']);

        $this->expectException(InvalidExecutionPlan::class);
        $node  = $graph->getNodeById(3);
        $this->assertFalse($node);

    }

    public function testAddNodeAfter(): void
    {
        $plan = file_get_contents(__DIR__ ."/fixtures/plan.json");

        $graph = new Graph($plan);

        $node1 =& $graph->addNodeAfter(1);
        $this->assertIsArray($node1);
        $this->assertArrayHasKey('id',$node1);
        $this->assertArrayHasKey('in',$node1);
        $this->assertArrayHasKey('out',$node1);
        $this->assertEquals(2, $node1['id']);
        $this->assertEquals([1], $node1['in']);
        $this->assertEquals([], $node1['out']);

        $firstNode =& $graph->getNodeById(1);
        $this->assertEquals([2], $firstNode['out']);

        // add node2 as sibling of node1
        $node2 =& $graph->addNodeAfter(1);
        $this->assertEquals(3, $node2['id']);
        $this->assertEquals([1], $node1['in']);
        $this->assertEquals([1], $node2['in']);
        $this->assertEquals([], $node2['out']);
        $this->assertEquals([2,3], $firstNode['out']);
    }

    public function testCopyLeaf(): void
    {
        $plan  = file_get_contents(__DIR__ ."/fixtures/graph.json");
        $graph = new Graph($plan);

        $this->assertCount(4,$graph->graph);
        $graph->copy(2);

        $this->assertCount(5,$graph->graph);

        $parent   =& $graph->getNodeById(1);
        $original =& $graph->getNodeById(2);
        $copy     =& $graph->getNodeById(4);
        $this->assertEquals([2,3,4], $parent['out']);
        $this->assertEquals([1], $original['in']);
        $this->assertEquals([],  $original['out']);
        $this->assertEquals([1], $copy['in']);
        $this->assertEquals([],  $copy['out']);
    }

    public function testCopyBranch(): void
    {
        $plan  = file_get_contents(__DIR__ ."/fixtures/graph.json");
        $graph = new Graph($plan);

        $this->assertCount(4,$graph->graph);
        $graph->copy(1);

        $this->assertCount(7,$graph->graph);

        $parent   =& $graph->getNodeById(0);
        $original =& $graph->getNodeById(1);
        $copy     =& $graph->getNodeById(4);
        $this->assertEquals([1,4], $parent['out']);
        $this->assertEquals([0],   $original['in']);
        $this->assertEquals([2,3], $original['out']);
        $this->assertEquals([0],   $copy['in']);
        $this->assertEquals([5,6], $copy['out']);

        $deepCopy1 = $graph->getNodeById(5);
        $deepCopy2 = $graph->getNodeById(6);
        $this->assertEquals([4],   $deepCopy1['in']);
        $this->assertEquals([], $deepCopy1['out']);

        $this->assertEquals([4],   $deepCopy2['in']);
        $this->assertEquals([], $deepCopy2['out']);
    }

    public function testCopyRoot(): void
    {
        $plan  = file_get_contents(__DIR__ ."/fixtures/graph.json");
        $graph = new Graph($plan);

        $this->expectException(InvalidExecutionPlan::class);
        $graph->copy(0);
    }
}
