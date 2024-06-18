<?php
declare(strict_types=1);
namespace Connector\Record;


/**
 * Base class for the implementation-specific RecordLocator.
 *
 * The RecordLocator provides the information needed to identify one or more records to extract or load.
 *
 * Implementation-specific properties are defined in child classes. They can be accessed from the base class using
 * magic accessors.
 *
 * Note: Optional properties must accept a null value.
 */
class RecordLocator
{

    public string $recordType = "default";
    private array $properties = [];

    public function __construct(mixed $params = null)
    {
        if($params instanceof RecordLocator) {
            $this->copyFrom($params);
        } elseif (is_array($params)) {
            foreach($params as $key=>$value) {
                $this->$key = $value;
            }
        }
    }

    public function __set(string $name, mixed $value): void
    {
        $this->properties[$name] = $value;
    }

    public function __get(string $name): mixed
    {
        return $this->properties[$name]??null;
    }

    public function __isset(string $name): bool
    {
        return isset($this->properties[$name]);
    }

    public function getProperties(): array
    {
        return $this->properties;
    }

    private function copyFrom(RecordLocator $locator): void
    {
        foreach($locator->getProperties() as $propertyName => $propertyValue) {
            $this->$propertyName = $propertyValue;
        }
        $this->recordType = $locator->recordType;
    }

    public function toJson()
    {
        return json_encode([
            '$schema'    => "https://json-schema.org/draft/2020-12/schema",
            '$id'        => "http://formassembly.com/connector/recordlocator",
            "title"      => "Record Locator",
            "type"       => "object",
            "properties" => $this,
        ]);

    }
}
