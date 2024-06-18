<?php

namespace Connector\Integrations\Database;

use Connector\Exceptions\NotImplemented;
use Connector\Integrations\AbstractIntegration;
use Connector\Integrations\Response;
use Connector\Mapping;
use Connector\Record\RecordKey;
use Connector\Record\RecordLocator;
use Connector\Record\Recordset;
use Connector\Schema\IntegrationSchema;

abstract class AbstractDatabase extends AbstractIntegration
{

    abstract protected function select(string $query):Recordset;
    abstract protected function insert(string $tableName, array $record):RecordKey;
    abstract protected function update(RecordKey $key, array $record);

    public function discover(): IntegrationSchema
    {
        throw new NotImplemented();
    }

    public function extract(RecordLocator $recordLocator, Mapping $mapping, ?RecordKey $scope): Response
    {
        throw new NotImplemented();
    }

    public function load(RecordLocator $recordLocator, Mapping $mapping, ?RecordKey $scope): Response
    {
        throw new NotImplemented();
    }
}
