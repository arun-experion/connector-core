<?php

namespace Connector\Schema;

use Connector\Exceptions\InvalidSchemaException;
use Connector\Type\DataType;

/**
 * Base class for schemas.
 * A schemas describes the types of records, with their respective properties, available in the integrated system.
 */
class IntegrationSchema
{
    public string $json = '';
    public array $schema;

    private string $timeZone = '';
    private string $locale   = '';

    public function __construct(array $schema = [])
    {
        $this->schema = $schema;
        $this->json   = json_encode($schema, JSON_UNESCAPED_SLASHES);
    }

    /**
     * The integration's supported locale (RFC 5646) E.g. en-US
     *
     * @return string
     */
    public function getLocale(): string
    {
        return $this->locale;
    }

    /**
     * @param string $locale (e.g. 'en-US')
     *
     * @return void
     */
    public function setLocale(string $locale): void
    {
        $this->locale = $locale;
    }

    /**
     * The integration's supported character encoding. E.g. UTF-8
     * @return string
     */
    public function getEncoding(): string
    {
        return "UTF-8";
    }

    /**
     * The integration's time-zone
     *
     * @return string
     */
    public function getTimeZone(): string
    {
        return $this->timeZone;
    }

    /**
     * @param $timeZone  (e.g. 'UTC' or 'America/New_York')
     *
     * @return void
     */
    public function setTimeZone($timeZone): void
    {
        $this->timeZone = $timeZone;
    }

    /**
     * Returns a DataType for the given $propertyName in the $recordtype,
     * with data type, format, time-zone,locale and encoding properly set.
     *
     * @param string $recordType
     * @param string $propertyName
     *
     * @return \Connector\Type\DataType
     * @throws \Connector\Exceptions\InvalidSchemaException
     */
    public function getDataType(string $recordType, string $propertyName): DataType
    {
        $property = $this->getProperty($recordType, $propertyName);

        $d = (new DataType())->fromJsonSchema(json_encode($property));
        $d->setTimeZone($this->getTimeZone());
        $d->setEncoding($this->getEncoding());
        $d->setLocale($this->getLocale());
        return $d;
    }

    /**
     * @param string $recordType
     * @param string $propertyName
     *
     * @return bool
     */
    public function hasProperty(string $recordType, string $propertyName): bool
    {
        try {
            $prop = $this->getProperty($recordType, $propertyName);
        } catch (InvalidSchemaException $exception) {
            return false;
        }
        return true;
    }

    /**
     * Retrieve the definition of $propertyName in $recordType.
     * E.g. field 'tfa_1' from Form in 'step_1', or column 'Email' from table 'Contact.
     *
     * @throws \Connector\Exceptions\InvalidSchemaException
     */
    protected function getProperty(string $recordType, string $propertyName): ?array
    {
        // Check that the record type (still) exists in the schema.
        if(!isset($this->schema['items'][$recordType])) {
            throw new InvalidSchemaException(sprintf("Could not find the record type '%s' in schema.", $recordType));
        }

        // Check that the schema is properly formed. We expect the 'properties' array, even if empty.
        if(!isset($this->schema['items'][$recordType]['properties'])) {
            throw new InvalidSchemaException(sprintf("Record type '%s' has no defined properties in schema.", $recordType));
        }

        $properties = $this->schema['items'][$recordType]['properties'];

        if(isset($this->schema['items'][$recordType]['anyOf'])) {
            foreach($this->schema['items'][$recordType]['anyOf'] as $anyOf) {
                $properties = array_merge($properties, $anyOf['properties']);
            }
        }

        // If the property doesn't exist, look for valid fallback
        if(!isset($properties[$propertyName])) {

            // Catch-all property.
            // See https://json-schema.org/understanding-json-schema/reference/object.html#additional-properties
            if(isset($this->schema['items'][$recordType]['additionalProperties']) &&
                $this->schema['items'][$recordType]['additionalProperties'] !== false
            ) {
                return $this->schema['items'][$recordType]['additionalProperties'];
            }

            // Nothing to fall back to, raise an error.
            throw new InvalidSchemaException(sprintf("Could not find the property '%s' in the '%s' schema.", $propertyName, $recordType));
        }

        $property = $properties[$propertyName];

        // Check if the property is a reference to a definition.
        // See https://json-schema.org/understanding-json-schema/structuring.html#defs
        if(isset($property['$ref'])) {

            // Check if the reference resolves to a definition.
            if(str_starts_with($property['$ref'],'#/$defs/')) {
                $ref = str_replace('#/$defs/', "", $property['$ref']);
                if(!isset($this->schema['$defs'][$ref])) {
                    throw new InvalidSchemaException(sprintf("Could not find the reference '%s' in schema definitions.", $ref));
                }
                // Get the property definition
                $property = $this->schema['$defs'][$ref];
            } else {
                $ref = ltrim($property['$ref'],"/");
                if(!isset($this->schema['items'][$ref])){
                    throw new InvalidSchemaException(sprintf("Could not find the reference '%s' in schema.", $ref));
                }
                $property = $this->schema['items'][$ref];
            }
        }

        return $property;
    }

    /**
     * Returns true if the property name is fully qualified, meaning that
     * it explicitly includes the record type ID, as colon-separated namespace.
     * e.g:  step_1:tfa_1
     * @param string $name
     *
     * @return bool
     */
    public function isFullyQualifiedName(string $name): bool
    {
        $names = explode(":", $name);
        return (count($names) > 1 && isset($this->schema['items'][$names[0]]));
    }

    /**
     * Ensure the property name is fully qualifie by prefixing the $recordTypeId if absent.
     * E.g: "tfa_1" => "step_1:tfa_1
     * @param string $recordTypeId
     * @param string $propertyName
     *
     * @return string
     */
    public function fullyQualifyName(string $recordTypeId, string $propertyName): string
    {
        if($this->isFullyQualifiedName($propertyName)) {
            return $propertyName;
        }
        return $recordTypeId . ":" . $propertyName;
    }

    /**
     * If the property name is fully qualified, returns the record type. Returns null otherwise.
     * E.g "step_1:tfa_1" => "step_1"
     * If more than one record types match, return the most specific record.
     * E.g "step_1:tfa_1:tfa_2" => "step_1:tfa_2"
     *
     * @param string $name
     *
     * @return string|null
     */
    public function getRecordTypeFromFQN(string $name, bool $greedy = true ): ?string
    {
        $parts = $this->splitFQN($name, $greedy);
        return $parts['recordType'];
    }

    /**
     * If the property name is fully qualified, returns the record type. Returns null otherwise.
     * E.g "step_1:tfa_1" => "step_1"
     * If more than one record types match, return the least specific record.
     * E.g "step_1:tfa_1:tfa_2" => "step_1"
     *
     * @param string $name
     *
     * @return string|null
     */
    public function getRootRecordTypeFromFQN(string $name): ?string
    {
        if($this->isFullyQualifiedName($name)) {
            return substr($name, 0, strpos($name, ":"));
        } else {
            return null;
        }
    }

    /**
     * Returns the property name in a fully qualified name.
     * recordtype[:subrecordtype]:propertyname[:variation]
     * E.g  "form_1:tfa_1"       => "tfa_1"
     *      "form_1:tfa_2:tfa_3" => "tfa_3"
     *      "form_1:tfa_4:name"  => "tfa_4"
     *
     * @param string $name
     * @param bool   $greedy
     *
     * @return string|null
     */
    public function getPropertyNameFromFQN(string $name, bool $greedy = true): ?string
    {
        $parts = $this->splitFQN($name, $greedy);
        return $parts['propertyName'];
    }

    private function splitFQN(string $fqn, bool $greedy = true ): array
    {
        $found = [ 'recordType' => null, 'propertyName' => null];
        if($this->isFullyQualifiedName($fqn)) {
            $names = explode(":", $fqn);
            $recordType = '';
            for($i=0;$i<count($names);$i++) {
                $recordType = ltrim($recordType . ':' . $names[$i],":");
                if(isset($names[$i+1]) && $this->hasProperty($recordType, $names[$i+1])) {
                    $found = [ 'recordType' => $recordType, 'propertyName' => $names[$i+1]];
                    if(!$greedy) {
                        return $found;
                    }
                } else {
                    if(isset($this->schema['items'][$recordType])) {
                        $found = ['recordType' => $recordType, 'propertyName' => null];
                        if(!$greedy) {
                            return $found;
                        }
                    }
                }
            }
        }
        return $found;
    }
}
