<?php

namespace Connector\Plan;

use Connector\Graph;

class Builder
{
    private Graph $plan;
    private int $currentNodeId = 0;
    private array $options = [];

    public function __construct(?Graph $plan = null)
    {
        $this->plan = $plan ?? new Graph();
    }

    static function create(): Builder
    {
        return new Builder();
    }

    public function addOperation(): self
    {
        $operation = new Builder\Operation($this);
        $node =& $this->plan->addNodeAfter(0);
        $node = array_merge($node, $operation->config);
        $this->currentNodeId = $node['id'];
        return $this;
    }

    public function after(int $afterId): self
    {
        $this->plan->moveNodeAfter($this->currentNodeId, $afterId);
        return $this;
    }

    public function then(): Builder
    {
        return $this;
    }



    /**
     * @throws \Connector\Exceptions\InvalidExecutionPlan
     */
    public function with(): Builder\Operation
    {
        return new Builder\Operation($this, $this->plan->getNodeById($this->currentNodeId));
    }

    public function toJSON(): string
    {
        return json_encode([
            "options"    => $this->options,
            "operations" => $this->plan->graph
        ]);
    }

}
