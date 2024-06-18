<?php
declare(strict_types=1);

namespace Connector\Type;

use Connector\Exceptions\InvalidSchemaException;

/**
 * Describes a field in a record, based on the JSON Schema Draft 2020-12 specifications
 * and augmented with localization information (character encoding, locale, and time-zone)
 *
 * See: https://www.learnjsonschema.com/2020-12/validation/
 * See also: https://formassembly.atlassian.net/wiki/spaces/ENG/pages/2658074625/Connector+Design#2.1-Data-Structures
 */
final class DataType
{
    public JsonSchemaTypes   $type;
    public JsonSchemaFormats $format;
    public string $pattern;
    public string $validation;
    public int $minLength = 0;
    public int $maxLength = 0;
    public float $minimum;
    public float $exclusiveMinimum;
    public float $exclusiveMaximum;
    public string $encoding;
    public string $locale;
    public string $timeZone;
    public array $allOf = [];
    public array $anyOf = [];
    public array $oneOf = [];
    private DataType $items;
    private array $prefixItems;


    /**
     * @param JsonSchemaTypes|string  $type
     * @param JsonSchemaFormats|string $format
     * @param string $validation
     * @param string $encoding
     * @param string $locale
     * @param string $timeZone
     */
    public function __construct(mixed $type = 'string', mixed $format = '', string $validation = '', string $encoding = 'UTF-8', string $locale = "en-US", string $timeZone = "UTC")
    {
        $this->setType($type);
        $this->setFormat($format);

        $this->validation = $validation;
        $this->encoding   = $encoding;
        $this->locale     = $locale;
        $this->timeZone   = $timeZone;
    }


    /**
     * @throws \Connector\Exceptions\InvalidSchemaException
     */
    public function fromJsonSchema(string $json): self
    {
        $this->unsetAll();
        $schema = json_decode($json);

        if(json_last_error() !== JSON_ERROR_NONE) {
            throw new InvalidSchemaException(json_last_error_msg());
        }

        if(isset($schema->allOf)) {
            $items = [];
            foreach($schema->allOf as $item) {
                $d = new DataType();
                $d->fromJsonSchema(json_encode($item));
                $items[] = $d;
            }
            $this->setAllOf($items);
        }

        if(isset($schema->anyOf)) {
            $items = [];
            foreach($schema->anyOf as $item) {
                $d = new DataType();
                $d->fromJsonSchema(json_encode($item));
                $items[] = $d;
            }
            $this->setAnyOf($items);
        }

        if(isset($schema->oneOf)) {
            $items = [];
            foreach($schema->oneOf as $item) {
                $d = new DataType();
                $d->fromJsonSchema(json_encode($item));
                $items[] = $d;
            }
            $this->setOneOf($items);
        }

        if(isset($schema->type)) {
            $this->setType($schema->type);
        }

        if(isset($schema->format)) {
            $this->setFormat($schema->format);
        }

        if(isset($schema->items)) {
            if(is_array($schema->items)){
                throw new InvalidSchemaException("Json Schema 2020-12 does not allow multiple values for 'items'");
            }
            $d = new DataType();
            $d->fromJsonSchema(json_encode($schema->items));
            $this->setItems($d);
        }

        if(isset($schema->prefixItems)) {
            $items = [];
            foreach($schema->prefixItems as $item) {
                $d = new DataType();
                $d->fromJsonSchema(json_encode($item));
                $items[] = $d;
            }
            $this->setPrefixItems($items);
        }

        if(isset($schema->maxLength)) {
            $this->setMaxLength((int) $schema->maxLength);
        }

        return $this;
    }

    private function unsetAll()
    {
        $clean = new self;
        foreach ($this as $key => $val){
            if (isset($clean->$key)){
                $this->$key = $clean->$key;
            }else{
                unset($this->$key);
            }
        }
    }

    public function getLocale()
    {
        return $this->locale;
    }

    public function setLocale(string $value): self
    {
        $this->locale = $value;
        return $this;
    }

    public function getTimeZone()
    {
        return $this->timeZone;
    }

    public function setTimeZone(string $value): self
    {
        $this->timeZone = $value;
        return $this;
    }

    /**
     * @return string
     */
    public function getPattern(): string
    {
        return $this->pattern;
    }

    /**
     * @param string $pattern
     */
    public function setPattern(string $pattern): self
    {
        $this->pattern = $pattern;
        return $this;
    }

    /**
     * @param int $minLength
     *
     * @return DataType
     */
    public function setMinLength(int $minLength): DataType
    {
        $this->minLength = $minLength;

        return $this;
    }

    /**
     * @return int
     */
    public function getMinLength(): int
    {
        return $this->minLength;
    }

    /**
     * @param int $maxLength
     *
     * @return DataType
     */
    public function setMaxLength(int $maxLength): DataType
    {
        $this->maxLength = $maxLength;

        return $this;
    }

    /**
     * 0 means no max length
     * @return int
     */
    public function getMaxLength(): int
    {
        return $this->maxLength;
    }

    /**
     * @param float $minimum
     *
     * @return DataType
     */
    public function setMinimum(float $minimum): DataType
    {
        $this->minimum = $minimum;

        return $this;
    }

    /**
     * @return float
     */
    public function getMinimum(): float
    {
        return $this->minimum;
    }

    /**
     * @param float $exclusiveMinimum
     *
     * @return DataType
     */
    public function setExclusiveMinimum(float $exclusiveMinimum): DataType
    {
        $this->exclusiveMinimum = $exclusiveMinimum;

        return $this;
    }

    /**
     * @return float
     */
    public function getExclusiveMinimum(): float
    {
        return $this->exclusiveMinimum;
    }

    /**
     * @param float $exclusiveMaximum
     *
     * @return DataType
     */
    public function setExclusiveMaximum(float $exclusiveMaximum): DataType
    {
        $this->exclusiveMaximum = $exclusiveMaximum;

        return $this;
    }

    /**
     * @return float
     */
    public function getExclusiveMaximum(): float
    {
        return $this->exclusiveMaximum;
    }

    /**
     * @param string $encoding
     *
     * @return DataType
     */
    public function setEncoding(string $encoding): DataType
    {
        $this->encoding = $encoding;

        return $this;
    }

    /**
     * @return string
     */
    public function getEncoding(): string
    {
        return $this->encoding;
    }

    /**
     * @param string $validation
     *
     * @return DataType
     */
    public function setValidation(string $validation): DataType
    {
        $this->validation = $validation;

        return $this;
    }

    /**
     * @return string
     */
    public function getValidation(): string
    {
        return $this->validation;
    }

    /**
     * @param \Connector\Type\JsonSchemaFormats|string $format
     *
     * @return DataType
     */
    public function setFormat(mixed $format): DataType
    {
        if($format instanceof JsonSchemaFormats) {
            $this->format  = $format;
        } else {
            $this->format  = JsonSchemaFormats::from( (string) $format);
        }
        return $this;
    }

    /**
     * @return \Connector\Type\JsonSchemaFormats
     */
    public function getFormat(): JsonSchemaFormats
    {
        return $this->format ?? JsonSchemaFormats::None;
    }

    /**
     * @param \Connector\Type\JsonSchemaTypes|string $type
     *
     * @return DataType
     */
    public function setType(mixed $type): DataType
    {
        if($type instanceof JsonSchemaTypes) {
            $this->type    = $type;
        } else {
            $this->type    = JsonSchemaTypes::from( (string) $type);
        }
        return $this;
    }

    /**
     * @return \Connector\Type\JsonSchemaTypes
     */
    public function getType(): JsonSchemaTypes
    {
        return $this->type;
    }

    /**
     * @param DataType $items
     *
     * @return DataType
     */
    public function setItems(DataType $items): DataType
    {
        $this->items = $items;
        return $this;
    }

    /**
     * @return DataType
     */
    public function getItems(): DataType
    {
        return $this->items;
    }

    public function hasItems(): bool
    {
        return $this->getType() === JsonSchemaTypes::Array && isset($this->items);
    }

    /**
     * @param array $prefixItems
     *
     * @return DataType
     */
    public function setPrefixItems(array $prefixItems): DataType
    {
        $this->prefixItems = $prefixItems;

        return $this;
    }

    /**
     * @return array
     */
    public function getPrefixItems(): array
    {
        return $this->prefixItems;
    }

    public function hasPrefixItems(): bool
    {
        return $this->getType() === JsonSchemaTypes::Array && isset($this->prefixItems);
    }

    public function getValueSeparator(): string
    {
        if($this->expectsFormat(JsonSchemaFormats::CommaSeparated)) {
            return ", ";
        }
        if($this->expectsFormat(JsonSchemaFormats::SpaceSeparated)) {
            return " ";
        }
        if($this->expectsFormat(JsonSchemaFormats::SemiColonSeparated)) {
            return "; ";
        }
        return "";
    }

    /**
     * @param DataType[] $oneOf
     *
     * @return $this
     */
    public function setAllOf(array $allOf): DataType
    {
        $this->allOf = $allOf;
        return $this;
    }

    /**
     * @param DataType[] $oneOf
     *
     * @return $this
     */
    public function setAnyOf(array $anyOf): DataType
    {
        $this->anyOf = $anyOf;
        return $this;
    }

    /**
     * @param DataType[] $oneOf
     *
     * @return $this
     */
    public function setOneOf(array $oneOf): DataType
    {
        $this->oneOf = $oneOf;
        return $this;
    }

    /**
     * @return DataType[]
     */
    public function getAllOf(): array
    {
        return $this->allOf;
    }

    /**
     * @return DataType[]
     */
    public function getAnyOf(): array
    {
        return $this->anyOf;
    }

    /**
     * @return DataType[]
     */
    public function getOneOf(): array
    {
        return $this->oneOf;
    }

    public function expectsFormat(JsonSchemaFormats $format): bool
    {
        if($this->hasFormat()) {
            foreach ($this->getAllOf() as $schema) {
                if ($schema->expectsFormat($format)) {
                    return true;
                }
            }
            foreach ($this->getAnyOf() as $schema) {
                if ($schema->expectsFormat($format)) {
                    return true;
                }
            }
            foreach ($this->getOneOf() as $schema) {
                if ($schema->expectsFormat($format)) {
                    return true;
                }
            }

            return ($this->getFormat() === $format && $format!==JsonSchemaFormats::None);
        } else {
            return (JsonSchemaFormats::None === $format);
        }
    }

    public function hasFormat():bool
    {
        foreach($this->getAllOf() as $schema) {
            if($schema->hasFormat()) {
                return true;
            }
        }
        foreach($this->getAnyOf() as $schema) {
            if($schema->hasFormat()) {
                return true;
            }
        }
        foreach($this->getOneOf() as $schema) {
            if($schema->hasFormat()) {
                return true;
            }
        }
        return ($this->getFormat() !== null && $this->getFormat() !== JsonSchemaFormats::None);
    }
}
