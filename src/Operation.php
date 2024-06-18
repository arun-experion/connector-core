<?php
declare(strict_types=1);

namespace Connector;

use Connector\Exceptions\InvalidExecutionPlan;
use Connector\Exceptions\InvalidMappingException;
use Connector\Exceptions\RecordNotFound;
use Connector\Integrations\AbstractIntegration;
use Connector\Mapping\Item;
use Connector\Record\RecordKey;
use Connector\Record\RecordLocator;
use Connector\Record\Recordset;
use Connector\Type\DataType;
use Connector\Type\JsonSchemaTypes;
use Connector\Type\TypedValue;
use Connector\Operation\Result;
use FormAssembly\Formula\Processors\PhpSpreadsheetProcessor;


/**
 * An Operation is a unit of work performed by a connector.
 *
 * The operation results in a record being extracted from the source integration, transformed, and loaded onto the
 * target integration.
 *
 * If the source integration extracts more than one record, only the first record is processed, the leftover
 * records are returned for further processing.
 * If the target integration returns a key identifying the loaded record, the record key is returned.
 * If the target integration returns one or more records, this recordset is returned for further processing.
 */
final class Operation
{
    private array $config;
    private AbstractIntegration $sourceIntegration;
    private AbstractIntegration $targetIntegration;
    protected array $log = [];

    public function __construct(
        array $config,
        AbstractIntegration $sourceIntegration,
        AbstractIntegration $targetIntegration
    ) {
        $this->config            = $config;
        $this->sourceIntegration = $sourceIntegration;
        $this->targetIntegration = $targetIntegration;
    }

    private function log(string $message): void
    {
        $this->log[] = $message;
    }

    public function getLog(): array
    {
        return $this->log;
    }

    /**
     * Returns the configuration, possibly altered after a run() if configuration included formulas and aliases.
     * @return array
     */
    public function getConfig(): array
    {
        return $this->config;
    }

    /**
     * @param \Connector\Record\RecordKey|null $sourceScope
     * @param \Connector\Record\RecordKey|null $targetScope
     * @param \Connector\Record|null           $preExtractedRecord
     *
     * @return \Connector\Operation\Result
     * @throws \Connector\Exceptions\InvalidExecutionPlan
     * @throws \Connector\Exceptions\InvalidMappingException
     * @throws \Connector\Exceptions\RecordNotFound
     * @throws \Connector\Exceptions\EmptyRecordException
     * @throws \Exception
     */
    public function run(?RecordKey $sourceScope, ?RecordKey $targetScope, Record $preExtractedRecord = null): Result
    {
        $result = new Result();

        // Source's record locator may contain formulas. We resolve it prior to extracting data from the source.
        $this->config['recordLocators']['source'] = $this->evaluate($this->config['recordLocators']['source']);

        // Extract a set of records from the source integration.
        // If executing an operation that has a parent in the DAG, provide the parent's RecordKey as a scoping resource.
        // If executing as part of an unrolled loop, provide previously extracted records.
        $recordset = $this->extract($sourceScope, $preExtractedRecord);

        // Target's record locator may contain formula & aliases. We resolve it prior to transforming & loading data in the target.
        $this->config['recordLocators']['target'] = $this->evaluate($this->config['recordLocators']['target'], $recordset[0]);

        // Transform the first record returned by evaluating formulas, resolving aliases, and converting data according
        // to the target's schema. Additional records, if any, will be processed here again when the loop is unrolled.
        $mapping   = $this->transform($this->config['mapping'], $this->config['recordLocators'], $recordset[0]);

        // Load transformed record onto the target integration.
        // If executing an operation that has a parent in the DAG, provide the parent's RecordKey as a scoping resource.
        [$key, $returned] = $this->load($this->config['recordLocators']['target'], $mapping, $targetScope);

        // Return the complete extracted record set, so that any additional record can be processed.
        $result->setExtractedRecordSet($recordset);

        // Return the record set returned by the target integration, it will be mapped back to the source integration.
        $result->setReturnedRecordSet($returned);

        // Return the key of the record created in the target. This will be passed on to dependent operations, as a
        // scoping mechanism.
        $result->setLoadedRecordKey($key);

        return $result;
    }

    /**
     * @param \Connector\Record\RecordKey|null $scope
     * @param \Connector\Record|null    $preExtractedRecord  If provided, skips record extraction from the source integration
     *
     * @return \Connector\Record\Recordset
     */
    public function extract(?RecordKey $scope, Record $preExtractedRecord = null): Recordset
    {
        if($preExtractedRecord) {
            $recordset     = new Recordset();
            $recordset[]   = $preExtractedRecord;
        } else {
            $recordLocator = new RecordLocator($this->config['recordLocators']['source']);
            $mapping       = $this->getExtractableMapping();
            $response      = $this->sourceIntegration->extract($recordLocator, $mapping, $scope);
            $recordset     = $response->getRecordset();
        }
        return $recordset;
    }

    /**
     * @throws \Connector\Exceptions\InvalidExecutionPlan
     * @throws \Connector\Exceptions\RecordNotFound
     * @throws \Exception
     */
    public function transform($mapping, $recordLocators, $record): array
    {
        foreach ($mapping as & $map) {

            // If the mapping refers to a field in the extracted record ('id' property is set)
            if (array_key_exists('id',$map['source'])) {

                try {
                    if (! $record) {
                        // extract() did not find any record. The mapping expected one, so abort.
                        throw new RecordNotFound();
                    }

                    if ($map['source']['id'] === '' || $map['source']['id'] === null) {
                        // Mapping isn't fully configured.
                        throw new InvalidMappingException("Incomplete mapping configuration. Source is not set.");
                    }

                    // Get source record type, either from the record locator or the mapping (if fully-qualified)
                    if ($this->sourceIntegration->getSchema()->isFullyQualifiedName($map['source']['id'])) {
                        $recordType   = $this->sourceIntegration->getSchema()->getRecordTypeFromFQN(
                            $map['source']['id']
                        );
                        $propertyName = $this->sourceIntegration->getSchema()->getPropertyNameFromFQN(
                            $map['source']['id']
                        );

                        if ($propertyName === null) {
                            throw new InvalidMappingException(
                                sprintf("Property %s not found in schema", $map['source']['id'])
                            );
                        }

                    } else {
                        $locatorCfg   = $recordLocators['source'];
                        $recordType   = (new RecordLocator($locatorCfg))->recordType;
                        $propertyName = $map['source']['id'];
                    }

                    // Get value from extracted record
                    $sourceValue = $record->getValue($map['source']['id']);

                    // Get value's data type
                    $sourceDataType = $this->sourceIntegration->getSchema()->getDataType($recordType, $propertyName);
                }
                catch (InvalidMappingException $exception) {
                    // The mapping is incomplete or outdated and cannot be resolved.
                    // The source is handled as an empty string, and we continue processing normally.
                    $sourceValue    = '';
                    $sourceDataType = new DataType(JsonSchemaTypes::String);
                    $this->log($exception->getMessage());
                }

            }
            // If the mapping refers to a formula, evaluate the formula, and assume a 'string' data type
            elseif (array_key_exists('formula',$map['source'])) {
                $sourceDataType = new DataType();
                $sourceValue    = $this->evaluate($map['source']['formula'], $record);
            }
            // Otherwise, the mapping is expected to be a static string
            elseif (array_key_exists('value',$map['source'])) {
                $sourceDataType = new DataType();
                $sourceValue    = $map['source']['value'];
            } else {
                throw new InvalidExecutionPlan();
            }

            // Choice mapping
            $sourceValue = $this->substituteValues($sourceValue, $map);

            try {
                // Check mapping configuration
                if (! array_key_exists('id', $map['target']) || $map['target']['id'] === ''
                    || $map['target']['id'] === null
                ) {
                    throw new InvalidMappingException("Incomplete mapping configuration. Target is not set.");
                }

                // Retrieve the data type expected at the destination
                if ($this->targetIntegration->getSchema()->isFullyQualifiedName($map['target']['id'])) {
                    $targetRecordType   = $this->targetIntegration->getSchema()->getRecordTypeFromFQN(
                        $map['target']['id']
                    );
                    $targetPropertyName = $this->targetIntegration->getSchema()->getPropertyNameFromFQN(
                        $map['target']['id']
                    );
                } else {
                    $targetRecordType   = $recordLocators['target']['recordType'] ?? "default";
                    $targetPropertyName = $map['target']['id'];
                }

                $targetDataType = $this->targetIntegration->getSchema()->getDataType(
                    $targetRecordType,
                    $targetPropertyName
                );
            } catch (InvalidMappingException $exception) {
                // The mapping is incomplete or outdated and cannot be resolved.
                // With no valid target, with skip to the next mapping and continue processing normally.
                $this->log($exception->getMessage());
                continue;
            }

            // Convert to expected data type
            $typedValue = $this->convert($sourceValue, $sourceDataType, $targetDataType);

            // Replace mapping with resolved value
            $map['source'] = ['value' => $typedValue->value];
        }

        return $mapping;
    }

    /**
     * @param array                            $recordLocator
     * @param array                            $mapping
     * @param \Connector\Record\RecordKey|null $scope
     *
     * @return array
     * @throws \Connector\Exceptions\EmptyRecordException
     */
    public function load(array $recordLocator, array $mapping,  ?RecordKey $scope = null): array
    {
        $recordLocator = new RecordLocator($recordLocator);
        $mapping       = $this->getLoadableMapping($mapping);
        $response      = $this->targetIntegration->load($recordLocator, $mapping, $scope);
        return [$response->getRecordKey(), $response->getRecordset()];
    }

    /**
     * Find a key in $map['transform'] that matches $search, and replace with the $map value.
     * $map['transform'] is user-generated, so we do a lenient, case-insensitive, whitespace trimmed match.
     *
     * @param mixed $search
     * @param array $map
     *
     * @return string[]|string
     */
    private function substituteValues(mixed $search, array $map): mixed
    {
        if(isset($map['transform'])) {
            if(is_array($search)) {
                foreach($search as $i => $v) {
                    $search[$i] = $this->substituteValues($v, $map);
                }
            } else {
                foreach($map['transform'] as $match => $replace) {
                    if(strtolower(trim($search)) === strtolower(trim($match))) {
                        if($replace) {
                            $search = $replace;
                        }
                        break;
                    }
                }
            }
        }
        return $search;
    }

    /**
     * @param mixed                    $value
     * @param \Connector\Type\DataType $sourceDataType
     * @param \Connector\Type\DataType $targetDataType
     *
     * @return \Connector\Type\TypedValue
     * @throws \Exception
     */
    private function convert(mixed $value, DataType $sourceDataType, DataType $targetDataType): TypedValue
    {
        return (new TypedValue($value, $sourceDataType))->convert($targetDataType);
    }

    /**
     * @param mixed $expression
     * @param Record $record
     *
     * @return mixed
     * @throws \Exception
     */
    private function evaluate(mixed $expression, Record $record = null): mixed
    {
        if(is_array($expression)) {
            $resolved = [];
            foreach($expression as $key => $value) {
                $resolved[$key] = $this->evaluate($value,$record);
            }
        } elseif(is_string($expression)) {
            $resolved = $expression;

            // Set proper locale before evaluating an expression that could be locale dependant (e.g @LOCALNOW())
            $this->matchTargetIntegrationLocale();
            try {
                $aliases  = $this->resolveAliases($resolved, $record);
                $resolved = (new PhpSpreadsheetProcessor())->evaluate($resolved, $aliases);
            } catch (\Throwable $exception) {
                $resolved = "#VALUE!";
            } finally {
                $this->restoreDefaultLocale();
            }
        } else {
            $resolved = $expression;
        }
        return $resolved;
    }

    private function matchTargetIntegrationLocale(): void
    {
        $locale   = $this->targetIntegration->getSchema()->getLocale();
        $timeZone = $this->targetIntegration->getSchema()->getTimeZone();
        Localization::setLocale($locale);
        Localization::setTimezone($timeZone);
    }

    private function restoreDefaultLocale(): void
    {
        Localization::setLocale("en_US");
        Localization::setTimezone("UTC");
    }


    private function extractAliasesFromFormula(?string $formula): array
    {
        if($formula) {
            preg_match_all("/%%([^%%]+)%%/U", $formula, $aliases, PREG_PATTERN_ORDER);
            return array_unique($aliases[1]);
        }
        return [];
    }

    /**
     * @throws \Exception
     */
    private function resolveAliases(?string $formula, ?Record $record): array
    {
        $aliasDic = [];
        if($record) {
            $aliases = $this->extractAliasesFromFormula($formula);
            foreach ($aliases as $alias) {
                $value = new TypedValue($record->getValue($alias), new DataType(JsonSchemaTypes::String));
                $aliasDic[$alias] = $value->value;
            }
        }
        return $aliasDic;
    }

    private function getLoadableMapping(array $mapping): Mapping
    {
        $loadMapping = new Mapping();
        foreach($mapping as $map) {
            if(isset($map['source']['value'])) {
                $loadMapping[] = new Item($map['target']['id'], $map['source']['value'],  $map['target']['label'] ?? null);
            }
        }
        return $loadMapping;
    }

    private function getExtractableMapping(): Mapping
    {
        $mapping = new Mapping();
        foreach($this->config['mapping'] as $map) {
            if(isset($map['source']['id'])) {
                $mapping[] = new Item($map['source']['id']);
            }
            elseif(isset($map['source']['formula'])) {
                $aliases = $this->extractAliasesFromFormula($map['source']['formula']);
                foreach($aliases as $alias) {
                    $mapping[] = new Item($alias);
                }
            }
        }
        foreach($this->config['recordLocators']['target'] as $locatorProperty) {
            $aliases = $this->extractAliasesFromFormula($locatorProperty);
            foreach($aliases as $alias) {
                $mapping[] = new Item($alias);
            }
        }
        return $mapping;
    }

}
