<?php

namespace Connector\Integrations;

use Connector\Record\RecordKey;
use Connector\Record\Recordset;

/**
 * Class representing the result of the execution of an integration.
 */
class Response
{
    public ?RecordKey $recordKey = null;
    public ?Recordset $recordset = null;

    private array $log = [];

    /**
     * Sets the list of records, either found as the result of an extract(),
     * or constructed as the result of the load()
     *
     * @param \Connector\Record\Recordset $recordset
     *
     * @return $this
     */
    public function setRecordset(Recordset $recordset): self
    {
        $this->recordset = $recordset;

        return $this;
    }

    /**
     * Sets the key of the created or updated record as the result of a load()
     *
     * @param \Connector\Record\RecordKey $recordKey
     *
     * @return $this
     */
    public function setRecordKey(RecordKey $recordKey): self
    {
        $this->recordKey = $recordKey;

        return $this;
    }

    /**
     * @return \Connector\Record\Recordset|null
     */
    public function getRecordset(): ?Recordset
    {
        return $this->recordset;
    }

    /**
     * @return \Connector\Record\RecordKey|null
     */
    public function getRecordKey(): ?RecordKey
    {
        return $this->recordKey;
    }

    /**
     * Return log produced by the Integration.
     * @return array
     */
    public function getLog(): array
    {
        return $this->log;
    }

    /**
     * Overwrite Integration log with $messages
     * @param array $log
     */
    public function setLog(array $log): void
    {
        $this->log = $log;
    }

    /**
     * Add a message or array of messages to the Integration log
     * @param string|string[] $messages
     *
     * @return void
     */
    public function log(mixed $messages): void
    {
        if(!empty($messages)) {
            if(is_array($messages)) {
                $this->log = array_merge($this->log, $messages);
            }
            else {
                $this->log[] = $messages;
            }
        }
    }
}
