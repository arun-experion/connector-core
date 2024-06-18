<?php

namespace Connector\Integrations\Fake;

use Connector\Exceptions\InvalidMappingException;
use Connector\Integrations\AbstractIntegration;
use Connector\Integrations\Response;
use Connector\Mapping;
use Connector\Record\RecordKey;
use Connector\Record\RecordLocator;
use Connector\Record\Recordset;
use Connector\Record;

/**
 * Fake integration used for unit testing.
 * Use insertRecord() and selectRecord() to set and inspect data
 */
class Integration extends AbstractIntegration
{
    private array $data;
    private \PDO $db;

    public function __construct()
    {
        $this->db   = new \PDO('sqlite::memory:');
        $this->data = [];
    }

    public function createTable(string $table, array $columns): void
    {
        // Set primary key
        array_unshift($columns, "id integer PRIMARY KEY AUTOINCREMENT");
        $columns   = implode(", ", $columns);
        $this->db->exec("CREATE TABLE IF NOT EXISTS $table (".$columns.")");
        $this->discover();
    }

    public function insertRecord(string $table, array $record): int
    {
        $validColumns = array_map(function ($col) { return $col['name'];}, $this->schema->getColumnsFromTable($table));
        $columns      = [];
        $values       = [];

        foreach($record as $column => $value) {

            if($this->getSchema()->isFullyQualifiedName($column)) {
                $columnTable  = $this->getSchema()->getRecordTypeFromFQN($column);
                if($columnTable === $table) {
                    $column = $this->getSchema()->getPropertyNameFromFQN($column);
                } else {
                    // Column from a different table. Joining tables not supported at the moment.
                    throw new InvalidMappingException('Joined tables not supported.');
                }
            }

            // Ignore invalid column name in query.
            if(in_array($column, $validColumns)) {
                $columns[] = $column;
                $values[]  = $value;
            }
        }

        $columns      = implode(",", $columns);
        $placeholders = implode(",", array_fill(0, count($values), '?'));

        $statement = $this->db->prepare( " INSERT INTO $table (".$columns.") VALUES (".$placeholders.")" );
        $statement->execute($values);
        return $this->db->lastInsertId();
    }

    public function selectRecord(string $table, int $recordKey): array
    {
        $statement = $this->db->prepare( " SELECT * FROM $table WHERE ID = ?");
        $statement->execute([$recordKey]);
        return $statement->fetch(\PDO::FETCH_ASSOC);
    }

    public function discover(): \Connector\Schema\IntegrationSchema
    {
        $this->schema = new FakeIntegrationSchema($this->db);
        return $this->schema;
    }

    public function extract(RecordLocator $recordLocator, Mapping $mapping, ?RecordKey $scope): Response
    {
        $records = new Recordset();
        $table   = $recordLocator->recordType;

        if($scope) {
            if($scope->recordType === $table) {
                $whereClause = " WHERE id = {$scope->recordId}";
            } else {
                // foreign key
                $whereClause = " WHERE {$scope->recordType}_id = {$scope->recordId}";
            }
        } else {
            $whereClause = "";
        }

        // Ignore invalid column name in query.
        $validColumns = array_map(function ($col) { return $col['name'];}, $this->schema->getColumnsFromTable($table));

        $columns = ['id'];
        foreach($mapping as $item) {

            if($this->getSchema()->isFullyQualifiedName($item->key)) {
                $columnTable  = $this->getSchema()->getRecordTypeFromFQN($item->key);
                if($columnTable === $table) {
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

            if(in_array($column, $validColumns)) {
                $columns[] = $alias;
            }
        }

        $columns   = implode(", ", $columns);
        $statement = $this->db->prepare("SELECT $columns FROM $table" . $whereClause);
        $statement->execute();
        $results   = $statement->fetchAll(\PDO::FETCH_ASSOC);

        foreach($results as $result) {
            $recordKey = new RecordKey($result['id'], $table);
            $records[] = new Record($recordKey, $result);
        }

        return (new Response())->setRecordset($records);
    }

    public function load(RecordLocator $recordLocator, Mapping $mapping, ?RecordKey $scope): Response
    {
        $record = [];
        foreach($mapping as $map) {
            $record[$map->key] = $map->value;
        }
        $id = $this->insertRecord($recordLocator->recordType, $record);
        return (new Response())->setRecordKey( new RecordKey($id, $recordLocator->recordType));
    }

    public function log(string $message):void
    {
        parent::log($message);
    }
}
