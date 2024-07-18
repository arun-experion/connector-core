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

    private array $emptyGraph = [ [ "id" => 0, "in" => [], "out" => [] ] ];

    public function __construct(?string $graph = null) {
        if(!$graph) {
            $this->graph = $this->emptyGraph;
        } else {
            $plan        = $this->parse($graph);
            $this->graph = $plan['operations'];
        }
    }

    protected function parse(string $graph): array {
        return json_decode($graph, true);
    }

    /**
     * @throws \Connector\Exceptions\InvalidExecutionPlan
     */
    public function & getNodeById(int $id): array {
        foreach($this->graph as &$node) {
            if($node['id'] === $id) return $node;
        }
        throw new InvalidExecutionPlan ("Invalid Node ID");
    }

    /**
     * Returns a unique identifier
     * @return int
     */
    private function getNewId():int {
        $maxId = 0;
        foreach($this->graph as $node) {
            if($node['id'] > $maxId)  {
                $maxId = $node['id'];
            }
        }
        return $maxId + 1;
    }

    /**
     * Copy a node and its descendents (a graph).
     * All nodes get a new id. The copied graph is a sibling of the original graph.
     * References to the original graph are remapped to the copied graph.
     *
     * @param int      $originalId  The id of node to be copied.
     * @param int|null $detachId    If provided, must be a parent or $originalId. Will detach node from its parent.
     *
     * @return int
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

        $this->updateReferences($originalId, $newId);

        return $newId;
    }

    /**
     *
     * @param int $movedNodeId
     * @param int $afterNodeId
     *
     * @return void
     * @throws \Connector\Exceptions\InvalidExecutionPlan
     */
    public function moveNodeAfter(int $movedNodeId, int $afterNodeId): void
    {
        $movedNode =& $this->getNodeById($movedNodeId);
        $afterNode =& $this->getNodeById($afterNodeId);

        // Detach
        foreach($movedNode['in'] as $parentId) {
            $parentNode =& $this->getNodeById($parentId);
            $parentNode['out'] = array_filter($parentNode['out'], function ($outId) use ($movedNodeId) {
                return $outId != $movedNodeId;
            });
        }

        // Attach
        $afterNode['out'][] = $movedNodeId;
        $movedNode['in'] = [$afterNodeId];
    }

    /**
     * @param int $operationId
     *
     * @return array
     * @throws \Connector\Exceptions\InvalidExecutionPlan
     */
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

    /**
     * @param int $id
     *
     * @return void
     */
    protected function markAsVisited(int $id): void {
        $this->visited[$id] = true;
    }

    /**
     * @param int $id
     *
     * @return bool
     */
    protected function wasVisited(int $id): bool
    {
        return array_key_exists($id, $this->visited) && $this->visited[$id] === true;
    }

    /**
     * @param int $oldOperationId
     * @param int $newOperationId
     *
     * @return void
     * @throws \Connector\Exceptions\InvalidExecutionPlan
     */
    private function updateReferences(int $oldOperationId, int $newOperationId): void
    {
        $node =& $this->getNodeById($newOperationId);
        foreach($node['out'] as $descendentId) {
            $this->updateReferenceRecursive($oldOperationId, $newOperationId, $descendentId);
        }
    }

    /**
     * @param int $oldOperationId
     * @param int $newOperationId
     * @param int $currentOperationId
     *
     * @return void
     * @throws \Connector\Exceptions\InvalidExecutionPlan
     */
    private function updateReferenceRecursive(int $oldOperationId, int $newOperationId, int $currentOperationId): void
    {
        $node =& $this->getNodeById($currentOperationId);

        if(isset($node['mapping'])) {
            foreach ($node['mapping'] as & $map) {
                if (isset($map['source']['operationId']) && $map['source']['operationId'] === $oldOperationId) {
                    $map['source']['operationId'] = $newOperationId;
                }
            }
        }
        foreach($node['out'] as $descendentId) {
            $this->updateReferenceRecursive($oldOperationId, $newOperationId, $descendentId);
        }
    }
}
