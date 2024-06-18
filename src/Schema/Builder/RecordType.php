<?php

namespace Connector\Schema\Builder;

/**
 * Describes a type of record (E.g a database table)
 */
class RecordType
{

    public string $name;
    public string $title = '';
    public string $description = '';
    public array $required = [];
    public array $anyOf = [];
    public RecordProperties $properties;
    private mixed $additionalProperties = false;
    public array $tags = [];
    public array $access = [];

    /**
     * @param string                                 $name
     * @param RecordProperties|RecordProperty[]|null $properties
     */
    public function __construct(string $name, mixed $properties = null)
    {
        $this->name = $name;
        if ($properties instanceof RecordProperties) {
            $this->properties = $properties;
        } elseif (is_array($properties)) {
            $this->properties = new RecordProperties();
            foreach ($properties as $property) {
                if ($property instanceof RecordProperty) {
                    $this->properties->add($property);
                } else {
                    throw new \InvalidArgumentException();
                }
            }
        } elseif (is_null($properties)) {
            $this->properties = new RecordProperties();
        } else {
            throw new \InvalidArgumentException();
        }
    }

    public function setTitle(string $title)
    {
        $this->title = $title;
    }

    public function setDescription(string $description)
    {
        $this->description = $description;
    }

    public function setRequired(array $required)
    {
        $this->required = $required;
    }

    /**
     *
     * "tags" is a custom keyword, part of the vocabulary defined in schema-integration.json
     *
     * @param string|string[] $tags
     *
     * @return void
     */
    public function setTags(mixed $tags)
    {
        $this->tags = is_array($tags) ? $tags : [$tags];
    }

    /**
     *
     * "access" is a custom keyword, part of the vocabulary defined in schema-integration.json
     *
     * @param string|string[] $access - one of "create", "read", "update"
     *
     * @return void
     */
    public function setAccess(mixed $access)
    {
        $this->access = is_array($access) ? $access : [$access];
    }

    /**
     * @param string|\Connector\Schema\Builder\RecordProperty $propertyOrName
     * @param array                                           $attributes
     *
     * @return void
     */
    public function addProperty(mixed $propertyOrName, array $attributes = [])
    {
        if ($propertyOrName instanceof RecordProperty) {
            if (count($attributes) > 0) {
                $propertyOrName->mergeAttributes($attributes);
            }
            $this->properties->add($propertyOrName);
        } else {
            $this->properties->add(new RecordProperty($propertyOrName, $attributes));
        }
    }

    /**
     * Not implemented
     * @param \Connector\Schema\Builder\RecordProperty $property
     *
     * @return void
     */
    public function addAnyOfProperty(RecordProperty $property)
    {
        $this->anyOf[] = ["properties" => [$property->name => $property->toArray()]];
    }

    /**
     * Optional - A catch-all definition for properties not explicitly listed.
     * see https://json-schema.org/understanding-json-schema/reference/object.html#additional-properties
     * @param array $attributes
     *
     * @return void
     */
    public function setAdditionalProperties(array $attributes)
    {
        $this->additionalProperties = $attributes;
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        $return = [
            "type" => "object",
            "properties"  => $this->properties->toArray()
        ];
        if($this->title) {
            $return['title'] = $this->title;
        }
        if($this->description) {
            $return['description'] = $this->description;
        }
        if(count($this->required) > 0) {
            $return['required'] = $this->required;
        }
        if(count($this->anyOf) > 0) {
            $return['anyOf'] = $this->anyOf;
        }
        if(count($this->tags) > 0) {
            $return['tags'] = $this->tags;
        }
        if(isset($this->additionalProperties)) {
            $return['additionalProperties'] = $this->additionalProperties;
        }

        return $return;
    }

}
