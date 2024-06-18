<?php

namespace Connector\Integrations;

use Connector\Schema\IntegrationSchema;
use Connector\Exceptions\UnsupportedFeature;
use Connector\Mapping;
use Connector\Record\RecordKey;
use Connector\Record\RecordLocator;


/**
 * Base class for integrations.
 *
 */
abstract class AbstractIntegration implements IntegrationInterface
{
    public IntegrationSchema $schema;
    protected array $log = [];

    /**
     * Set the schema for the integration.
     *
     * @param \Connector\Schema\IntegrationSchema $schema
     * @param string                              $defaultLocale   (e.g. 'en-US')
     * @param string                              $defaultTimeZone (e.g. 'UTC' or 'America/New_York')
     *
     * @return $this
     */
    public function setSchema(IntegrationSchema $schema, string $defaultLocale = '', string $defaultTimeZone = ''): self {
        $this->schema = $schema;
        if($this->schema->getLocale() === '') {
            $this->schema->setLocale($defaultLocale);
        }
        if($this->schema->getTimeZone() === '') {
            $this->schema->setTimeZone($defaultTimeZone);
        }
        return $this;
    }

    protected function log(string $message): void
    {
        $this->log[] = $message;
    }

    public function getLog(): array
    {
        return $this->log;
    }

    /**
     * @return \Connector\Schema\IntegrationSchema
     */
    public function getSchema(): IntegrationSchema
    {
        return $this->schema;
    }

    /**
     * Method executed at the beginning of a transaction.
     * @codeCoverageIgnore
     * @return void
     */
    public function begin(): void
    {}

    /**
     * Method executed if a transaction must be rolled back.
     *
     * @return void
     * @throws \Connector\Exceptions\UnsupportedFeature
     */
    public function rollback(): void
    { throw new UnsupportedFeature(); }

    /**
     * Method executed at the end of a transaction.
     * @codeCoverageIgnore
     * @return void
     */
    public function end(): void
    {}

    /**
     * discover() returns the integration's schema as a JSON Schema.
     * @return IntegrationSchema
     */
    public abstract function discover(): IntegrationSchema;

    /**
     * Extracts one or more records (of the same type).
     * Expected to return a Recordset in its response.
     *
     * @param \Connector\Record\RecordLocator  $recordLocator
     * @param \Connector\Mapping     $mapping
     * @param \Connector\Record\RecordKey|null $scope
     *
     * @return \Connector\Integrations\Response
     */
    public abstract function extract(RecordLocator $recordLocator, Mapping $mapping, ?RecordKey $scope): Response;

    /**
     * Loads a single record (covers both creating and updating records)
     * Expected to return a RecordKey in its response.
     *
     * @param \Connector\Record\RecordLocator  $recordLocator
     * @param \Connector\Mapping     $mapping
     * @param \Connector\Record\RecordKey|null $scope
     *
     * @return \Connector\Integrations\Response
     * @throws \Connector\Exceptions\EmptyRecordException
     */
    public abstract function load(RecordLocator $recordLocator, Mapping $mapping, ?RecordKey $scope): Response;
}
