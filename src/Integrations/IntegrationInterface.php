<?php

namespace Connector\Integrations;

use Connector\Schema\IntegrationSchema;
use Connector\Mapping;
use Connector\Record\RecordKey;
use Connector\Record\RecordLocator;

Interface IntegrationInterface
{

    /**
     * Set the schema for the integration.
     *
     * @param \Connector\Schema\IntegrationSchema $schema
     * @param string                              $defaultLocale   (e.g. 'en-US')
     * @param string                              $defaultTimeZone (e.g. 'UTC' or 'America/New_York')
     *
     * @return $this
     */
    public function setSchema(IntegrationSchema $schema, string $defaultLocale = '', string $defaultTimeZone = ''): self;

    /**
     * @return \Connector\Schema\IntegrationSchema
     */
    public function getSchema(): IntegrationSchema;

    /**
     * Return user-facing informational messages for logging purposes.
     * @return string[]
     */
    public function getLog(): array;

    /**
     * Method executed at the beginning of a transaction.
     * @return void
     */
    public function begin(): void;

    /**
     * Method executed if a transaction must be rolled back.
     *
     * @return void
     * @throws \Connector\Exceptions\UnsupportedFeature
     */
    public function rollback(): void;

    /**
     * Method executed at the end of a transaction.
     * @return void
     */
    public function end(): void;

    /**
     * discover() returns the integration's schema as a JSON Schema.
     * @return IntegrationSchema
     */
    public function discover(): IntegrationSchema;

    /**
     * Extracts one or more records (of the same type).
     * Expected to return a Recordset in its response.
     *
     * @param \Connector\Record\RecordLocator   $recordLocator
     * @param \Connector\Mapping                $mapping
     * @param \Connector\Record\RecordKey|null  $scope
     *
     * @return \Connector\Integrations\Response
     * @throws \Connector\Exceptions\InvalidExecutionPlan
     * @throws \Connector\Exceptions\InvalidSchemaException
     * @throws \Connector\Exceptions\RecordNotFound
     * @throws \Connector\Exceptions\SkippedOperationException
     * @throws \Connector\Exceptions\AbortedOperationException
     */
    public function extract(RecordLocator $recordLocator, Mapping $mapping, ?RecordKey $scope): Response;

    /**
     * Loads a single record (covers both creating and updating records)
     * Expected to return a RecordKey in its response.
     *
     * @param \Connector\Record\RecordLocator   $recordLocator
     * @param \Connector\Mapping                $mapping
     * @param \Connector\Record\RecordKey|null  $scope
     *
     * @return \Connector\Integrations\Response
     * @throws \Connector\Exceptions\EmptyRecordException
     * @throws \Connector\Exceptions\InvalidExecutionPlan
     * @throws \Connector\Exceptions\InvalidSchemaException
     * @throws \Connector\Exceptions\SkippedOperationException
     * @throws \Connector\Exceptions\AbortedOperationException
     */
    public function load(RecordLocator $recordLocator, Mapping $mapping, ?RecordKey $scope): Response;
}
