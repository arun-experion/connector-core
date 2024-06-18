<?php

namespace Connector;

use Connector\Exceptions\InvalidExecutionPlan;


/**
 * Simple implementation of a Directed Acyclic Graph.
 */
class Graph
{
    public array $graph;
    protected array $visited = [];

    private string $emptyGraph = <<<'JSON'
{
    "$schema"   : "https://formassembly.com/connectors/execution-plan.schema.json",           
    "source"    : "",
    "target"    : "",
    "operations": [ { "id": 0, "in": [], "out": [] } ]
}
JSON;

    public function __construct(?string $graph) {
        if(!$graph) {
            $graph = $this->emptyGraph;
        }
        $plan = $this->parse($graph);
        $this->graph = $plan['operations'];
    }

    protected function parse(string $graph): array {
        return json_decode($graph, true);
    }

    /**
     * @throws \Connector\Exceptions\InvalidExecutionPlan
     */
    public function & getNodeById($id): array {
        foreach($this->graph as &$node) {
            if($node['id'] === $id) return $node;
        }
        throw new InvalidExecutionPlan ("Invalid Node ID");
    }

    private function getNewId() {
        $maxId = 0;
        foreach($this->graph as $node) {
            if($node['id'] > $maxId)  {
                $maxId = $node['id'];
            }
        }
        return $maxId + 1;
    }

    /**
     * @throws \Connector\Exceptions\InvalidExecutionPlan
     */
    public function copy(int $originalId, int $detachId = null): int {

        if($originalId===0) {
            throw new InvalidExecutionPlan("Root node cannot be copied.");
        }
        $originalNode =& $this->getNodeById($originalId);
        $newId   = $this->getNewId();
        $newNode = $originalNode;
        $newNode['id'] = $newId;
        $this->graph[] =& $newNode;

        foreach($newNode['in'] as $parentId) {
            if($parentId !== $detachId) {
                $parentNode =& $this->getNodeById($parentId);
                $parentNode['out'][] = $newId;
            }
        }

        if($detachId) {
            $newNode['in'] = array_filter($newNode['in'], function($inId) use ($detachId) {
                return $inId != $detachId;
            });
        }

        $newNode['out'] = [];
        foreach($originalNode['out'] as $originalChildId) {
            $newChildId = $this->copy($originalChildId, $originalId);
            $newNode['out'][] = $newChildId;
            $childNode =& $this->getNodeById($newChildId);
            $childNode['in'][] = $newId;
        }

        return $newId;
    }

    public function & addNodeAfter(int $operationId): array
    {
        $insertNode =& $this->getNodeById($operationId);
        $newId      = $this->getNewId();
        $newNode    = [
            'id'  => $newId,
            'in'  => [$insertNode['id']],
            'out' => []
        ];
        $insertNode['out'][] = $newId;
        $this->graph[] = $newNode;
        return $this->graph[count($this->graph)-1];
    }

    protected function markAsVisited(int $id): void {
        $this->visited[$id] = true;
    }

    protected function wasVisited(int $id): bool
    {
        return array_key_exists($id, $this->visited) && $this->visited[$id] === true;
    }

}
