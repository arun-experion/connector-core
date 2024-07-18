<?php

namespace Connector\Integrations\Document;

use Connector\Exceptions\NotImplemented;
use Connector\Execution;
use Connector\Integrations\AbstractIntegration;
use Connector\Integrations\Response;
use Connector\Schema\GenericSchema;
use Connector\Schema\IntegrationSchema;
use Connector\Mapping;
use Connector\Record\RecordKey;
use Connector\Record\RecordLocator;

abstract class AbstractDocument extends AbstractIntegration
{
    protected mixed $template = null;
    protected mixed $document = null;
    protected Execution $buildPlan;
    private static int $nextFragmentId = 1;

    abstract public function buildPlan(AbstractIntegration $source): Execution;
    abstract protected function createDocumentFragment(RecordLocator $config, Mapping $mapping, ?RecordKey $scope): mixed;
    abstract protected function composeDocument(mixed $document, $documentFragment, int $fragmentId): mixed;
    abstract public function getDocument(): mixed;

    public function __construct()
    {
        $this->schema = $this->discover();
    }

    final public function extract(RecordLocator $recordLocator, Mapping $mapping, ?RecordKey $scope): Response
    {
        throw new NotImplemented();
    }

    final public function load(RecordLocator $recordLocator, Mapping $mapping, ?RecordKey $scope): Response
    {
        $documentFragment = $this->createDocumentFragment($recordLocator, $mapping, $scope);
        $this->document   = $this->composeDocument($this->document, $documentFragment, self::$nextFragmentId);
        $key = new RecordKey(self::$nextFragmentId++,$recordLocator->recordType);
        return (new Response())->setRecordKey($key);
    }

    public function discover(): IntegrationSchema
    {
        return new GenericSchema("Plain Text Document");
    }

    public function setTemplate(mixed $template): void
    {
        $this->template = $template;
        $this->document = $template;
    }

}
