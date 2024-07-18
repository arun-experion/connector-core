<?php

namespace Connector\Integrations\Fake;

use Connector\Exceptions\EmptyRecordException;
use Connector\Exceptions\InvalidExecutionPlan;
use Connector\Exceptions\InvalidMappingException;
use Connector\Integrations\AbstractIntegration;
use Connector\Integrations\Response;
use Connector\Mapping;
use Connector\Operation\Result;
use Connector\Record\DeferredRecord;
use Connector\Record\RecordKey;
use Connector\Record\RecordLocator;
use Connector\Record\Recordset;
use Connector\Record;

/**
 * in-memory SQLite-based integration, used for unit testing only.
 * Use insertRecord() and selectRecord() to set and inspect data
 */
class Integration extends AbstractIntegration
{
    private \PDO $db;

    public function __construct()
    {
        $this->db   = new \PDO('sqlite::memory:');
    }

    public function createTable(string $table, array $columns): void
    {
        // Set primary key
        array_unshift($columns, "id integer PRIMARY KEY AUTOINCREMENT");
        $columns = implode(", ", $columns);
        $this->db->exec("CREATE TABLE IF NOT EXISTS $table (" . $columns . ")");
        $this->discover();
    }

    /**
     * @throws \Connector\Exceptions\InvalidExecutionPlan
     * @throws \Connector\Exceptions\InvalidMappingException
     * @throws \Connector\Exceptions\EmptyRecordException
     */
    public function insertRecord(string $table, array $record): int
    {
        $validColumns = array_map(function ($col) {
            return $col['name'];
        }, $this->schema->getColumnsFromTable($table));

        if (count($validColumns) === 0) {
            throw new InvalidExecutionPlan('No columns defined for table "' . $table . '"');
        }
        $columns = [];
        $values  = [];

        foreach ($record as $column => $value) {

            if ($this->getSchema()->isFullyQualifiedName($column)) {
                $columnTable = $this->getSchema()->getRecordTypeFromFQN($column);
                if ($columnTable === $table) {
                    $column = $this->getSchema()->getPropertyNameFromFQN($column);
                } else {
                    // Column from a different table. Joining tables not supported at the moment.
                    throw new InvalidMappingException('Joined tables not supported.');
                }
            }

            // Ignore invalid column name in query.
            if (in_array($column, $validColumns)) {
                $columns[] = $column;
                $values[]  = $value;
            }
        }

        if(count($columns) === 0) {
            // no data mapped.
            throw new EmptyRecordException();
        }

        $columns      = implode(",", $columns);
        $placeholders = implode(",", array_fill(0, count($values), '?'));
        $query        = "INSERT INTO $table (" . $columns . ") VALUES (" . $placeholders . ")";
        $statement    = $this->db->prepare($query);

        $statement->execute($values);
        $recordKey = $this->db->lastInsertId();
        $this->log("Inserted $table record, ID: $recordKey");
        return $recordKey;
    }

    public function selectRecord(string $table, int $recordKey): array
    {
        $statement = $this->db->prepare(" SELECT * FROM $table WHERE ID = ?");
        $statement->execute([$recordKey]);
        return $statement->fetch(\PDO::FETCH_ASSOC);
    }

    public function updateRecord(string $table, array $record, int $recordKey): int
    {
        $columns = [];
        foreach ($record as $column => $value) {
            $columns[] = $column . " = ?";
        }
        $columns   = implode(',', $columns);
        $sql       = " UPDATE $table SET $columns WHERE ID = ?";
        $statement = $this->db->prepare($sql);
        $statement->execute([...array_values($record), $recordKey]);
        $this->log("Updated $table record, ID: $recordKey");
        return $recordKey;
    }

    public function selectAllRecords(string $table): array
    {
        $statement = $this->db->prepare(" SELECT * FROM $table");
        $statement->execute();

        return $statement->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function discover(): \Connector\Schema\IntegrationSchema
    {
        $this->schema = new FakeIntegrationSchema($this->db);
        return $this->schema;
    }

    public function begin(): void
    {
        // Ensure clean logs when running unit tests.
        $this->log = [];
    }
    /**
     * @throws \Connector\Exceptions\InvalidMappingException
     * @throws \Connector\Exceptions\RecordNotFound
     */
    public function extract(RecordLocator $recordLocator, Mapping $mapping, ?RecordKey $scope): Response
    {
        // Ensure clean logs when running unit tests.
        $this->log = [];

        $records = new Recordset();
        $table   = $recordLocator->recordType;

        if ($scope) {
            if ($scope->recordType === $table) {
                $whereClause = " WHERE id = {$scope->recordId}";
            } else {
                // foreign key
                $whereClause = " WHERE {$scope->recordType}_id = {$scope->recordId}";
            }
        } else {
            $whereClause = "";
        }

        // Ignore invalid column name in query.
        $validColumns = array_map(function ($col) {
            return $col['name'];
        }, $this->schema->getColumnsFromTable($table));

        $columns = ['id'];
        foreach ($mapping as $item) {

            if ($this->getSchema()->isFullyQualifiedName($item->key)) {
                $columnTable = $this->getSchema()->getRecordTypeFromFQN($item->key);
                if ($columnTable === $table) {
                    $column = $this->getSchema()->getPropertyNameFromFQN($item->key);
                    $alias  = $column . " as \"" . $item->key . "\"";
                } else {
                    // Column from a different table. Joining tables not supported at the moment.
                    throw new InvalidMappingException('Joined tables not supported.');
                }
            } else {
                $column = $item->key;
                $alias  = $column;
            }

            if (in_array($column, $validColumns)) {
                $columns[] = $alias;
            }
        }

        $columns   = implode(", ", $columns);
        $statement = $this->db->prepare("SELECT $columns FROM $table" . $whereClause);
        $statement->execute();
        $results = $statement->fetchAll(\PDO::FETCH_ASSOC);
        $recordCount = count($results);
        $this->log("Selected $recordCount $table record(s)");

        foreach ($results as $result) {
            $recordKey = new RecordKey($result['id'], $table);
            $records[] = new Record($recordKey, $result);
        }

        return (new Response())->setRecordset($records);
    }

    /**
     * @param \Connector\Record\RecordLocator  $recordLocator
     * @param \Connector\Mapping               $mapping
     * @param \Connector\Record\RecordKey|null $scope
     *
     * @return \Connector\Integrations\Response
     * @throws \Connector\Exceptions\InvalidExecutionPlan
     * @throws \Connector\Exceptions\InvalidMappingException
     * @throws \Connector\Exceptions\EmptyRecordException
     */
    public function load(RecordLocator $recordLocator, Mapping $mapping, ?RecordKey $scope): Response
    {
        // Ensure clean logs when running unit tests.
        $this->log = [];
        $record = [];
        foreach ($mapping as $map) {
            $record[$map->key] = $map->value;
        }
        if (isset($recordLocator->recordKey)) {
            $id = $this->updateRecord($recordLocator->recordType, $record, $recordLocator->recordKey->recordId);
        } else {
            $id = $this->insertRecord($recordLocator->recordType, $record);
        }

        $key         = new RecordKey($id, $recordLocator->recordType);
        $recordset   = new Recordset();
        $recordset[] = new Record($key, ['id' => $key->recordId]);
        return (new Response())->setRecordKey($key)->setRecordset($recordset);
    }

    public function log(mixed $message): void
    {
        parent::log($message);
    }
}
