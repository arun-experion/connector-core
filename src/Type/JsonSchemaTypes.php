<?php

namespace Connector\Type;

/**
 * Supported data types
 * see https://json-schema.org/understanding-json-schema/reference/type.html
 */
enum JsonSchemaTypes: string {
    case Number  = 'number';
    case Integer = 'integer';
    case String  = 'string';
    case Boolean = 'boolean';
    case Array   = 'array';
    case Object  = 'object';
    case Null    = 'null';
}