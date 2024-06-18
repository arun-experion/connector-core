<?php
declare(strict_types=1);

namespace Connector;

use Connector\Exceptions\EmptyRecordException;
use Connector\Integrations\AbstractIntegration;
use Connector\Record\RecordKey;
use Connector\Record\Recordset;
use Connector\Operation\Result;


/**
 * Core connector execution class.
 */
final Class Execution extends Graph
{
    private AbstractIntegration $sourceIntegration;
    private AbstractIntegration $targetIntegration;

    /**
     * @var array Set of Records extracted by Operations. Indexed by Operation ID.
     */
    private array $sourceRecords = [];

    public function __construct(string $executionPlan, AbstractIntegration $sourceIntegration, AbstractIntegration $targetIntegration) {
        parent::__construct($executionPlan);
        $this->sourceIntegration = $sourceIntegration;
        $this->targetIntegration = $targetIntegration;
    }

    /**
     * @throws \Connector\Exceptions\InvalidExecutionPlan
     * @throws \Connector\Exceptions\InvalidMappingException
     * @throws \Connector\Exceptions\RecordNotFound
     */
    public function run(RecordKey $sourceScope = null): void
    {
        $this->targetIntegration->begin();

        $this->runTransaction(0, $sourceScope);

        $this->targetIntegration->end();
    }

    /**
     * @param int                              $currentId
     * @param \Connector\Record\RecordKey|null $sourceScope
     * @param \Connector\Record\RecordKey|null $targetScope
     *
     * @throws \Connector\Exceptions\InvalidExecutionPlan
     * @throws \Connector\Exceptions\InvalidMappingException
     * @throws \Connector\Exceptions\RecordNotFound
     */
    private function runTransaction(int $currentId = 0, RecordKey $sourceScope = null, RecordKey $targetScope = null): void
    {
        if (!$this->wasVisited($currentId)) {

            if($currentId) { // first entry is no-op.

                $op = new Operation($this->getNodeById($currentId), $this->sourceIntegration, $this->targetIntegration);

                try {
                    $result = $op->run($sourceScope, $targetScope, $this->getSourceRecord($currentId));
                } catch(EmptyRecordException $exception) {
                    // The integration found no data to work with. The operation and its descendents are skipped.
                    // TODO: Consider logging
                    return;
                }
                finally {
                    $this->markAsVisited($currentId);
                }

                $sourceScope = $result->getExtractedRecordKey();
                $targetScope = $result->getLoadedRecordKey();
                $this->processResult($result, $currentId, $sourceScope, $targetScope);
            }

            while($nextId = current($this->graph[$currentId]['out'])) {
                $this->runTransaction($nextId, $sourceScope, $targetScope);
                next($this->graph[$currentId]['out']);
            }
        }
    }

    /**
     * @param \Connector\Operation\Result  $result
     * @param int                         $currentOperationId
     * @param \Connector\Record\RecordKey $sourceScope
     * @param \Connector\Record\RecordKey $targetScope
     *
     * @return void
     * @throws \Connector\Exceptions\InvalidExecutionPlan
     * @throws \Connector\Exceptions\InvalidMappingException
     * @throws \Connector\Exceptions\RecordNotFound
     */
    private function processResult(Result $result, int $currentOperationId, RecordKey $sourceScope, RecordKey $targetScope): void
    {
        // If the Operation extracted more than one records, only the first one was processed.
        // The leftover is added to the execution plan to be processed over the next iterations.
        $this->unroll($currentOperationId, $result->getExtractedRecordSet());

        // If the Load Operation returned a set of records and the configuration provides a result mapping,
        // we process each record through a new "reversed" Operation (where the flow of data is reversed)
        $config =& $this->getNodeById($currentOperationId);

        if(isset($config['resultMapping']) && $result->hasReturnedRecords()) {

            $reverseOperationCfg =& $this->addNodeAfter($currentOperationId);
            $reverseOperationCfg["recordLocators"] = [
                'source' => $config['recordLocators']['target'],
                'target' => $config['recordLocators']['source']
            ];
            $reverseOperationCfg["mapping"] = $config['resultMapping'];

            $reverseOperation = new Operation($reverseOperationCfg, $this->targetIntegration, $this->sourceIntegration);

            foreach($result->getReturnedRecordSet()->records as $record) {
                $this->setSourceRecord($reverseOperationCfg['id'], $record);
                $reverseOperation->run($targetScope, $sourceScope, $record);
                $this->markAsVisited($reverseOperationCfg['id']);
            }
        }
    }

    /**
     * @param int                         $operationId
     * @param \Connector\Record\Recordset $recordset
     *
     * @return void
     * @throws \Connector\Exceptions\InvalidExecutionPlan
     */
    private function unroll(int $operationId, Recordset $recordset): void
    {
        for ($i = 1; $i < count($recordset); $i++) {
            $newId = $this->copy($operationId);
            $this->setSourceRecord($newId, $recordset[$i]);
            $this->setTargetRecordIndex($newId, $i);
        }
    }

    /**
     * Stores the Record extracted by a given Operation.
     *
     * @param int                      $operationId
     * @param \Connector\Record $record
     *
     * @return void
     */
    private function setSourceRecord(int $operationId, Record $record): void
    {
        $this->sourceRecords[$operationId] = $record;
    }

    private function getSourceRecord(int $operationId): ?Record
    {
        return $this->sourceRecords[$operationId] ?? null;
    }

    private function setTargetRecordIndex(int $operationId, int $index): void
    {
        $node =& $this->getNodeById($operationId);
        $node['recordLocators']['target']["index"] = $index;
    }

}
