<?php

namespace Connector\Plan\Builder;

use Connector\Plan\Builder;

class Operation
{
    public array $config;

    public function __construct(public Builder $builder, array & $config = null)
    {
        if($config) {
            $this->config =& $config;
        } else {
            $this->config
                = [
                "recordLocators" => [
                    "source" => [],
                    "target" => [],
                ],
                "mapping"        => [],
                "resultMapping"  => [],
            ];
        }
    }

    public function setRecordTypes(string $source, string $target): self
    {
        $this->setSourceRecordType($source);
        $this->setTargetRecordType($target);
        return $this;
    }

    public function setSourceRecordType(string $recordType): self
    {
        $this->config['recordLocators']['source']['recordType'] = $recordType;
        return $this;
    }

    public function setTargetRecordType(string $recordType): self
    {
        $this->config['recordLocators']['target']['recordType'] = $recordType;
        return $this;
    }

    public function setSourceRecordTypeProperty(string $propertyName, string $propertyValue): self
    {
        $this->config['recordLocators']['source'][$propertyName] = $propertyValue;
        return $this;
    }

    public function setTargetRecordTypeProperty(string $propertyName, string $propertyValue): self
    {
        $this->config['recordLocators']['target'][$propertyName] = $propertyValue;
        return $this;
    }

    public function mapProperty(string $sourceId, string $targetId): self
    {
        $this->config['mapping'][] = [ 'source' => ['id' => $sourceId], 'target' => ['id' => $targetId]];
        return $this;
    }

    public function mapResult(string $sourceId, string $targetId): self
    {
        $this->config['resultMapping'][] = [ 'source' => ['id' => $sourceId], 'target' => ['id' => $targetId]];
        return $this;
    }

    public function mapFormula(string $formula, string $targetId): self
    {
        $this->config['mapping'][] = [ 'source' => ['formula' => $formula], 'target' => ['id' => $targetId]];
        return $this;
    }

    public function then(): Builder
    {
        return $this->builder;
    }

    public function toJSON(): string
    {
        return $this->builder->toJSON();
    }
}
