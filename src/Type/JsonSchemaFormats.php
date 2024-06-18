<?php

namespace Connector\Type;

/**
 * Supported string formats
 * see https://json-schema.org/understanding-json-schema/reference/string.html#format
 */
enum JsonSchemaFormats: string {

    /**
     * A string in a ISO8601 date format (e.g "2023-12-31")
     */
    case Date = 'date';

    /**
     * A string in a ISO8601 date-time format (e.g. "2023-12-31T15:35:59+00:00")
     */
    case DateTime = 'date-time';

    /**
     * A string in a ISO8601 time format (e.g. "15:35:59+00:00")
     */
    case Time = 'time';

    /**
     * A string in a localized date format (e.g. "31.12.2023")
     */
    case LocalDate = 'local-date';

    /**
     * A string in a localized date and time format (e.g. "31.12.2023 15:35")
     */
    case LocalDateTime = 'local-date-time';

    /**
     * A string in a localized time (e.g. "3:30 PM")
     */
    case LocalTime = 'local-time';

    /**
     * A universal resource identifier (URI), according to RFC3986.
     */
    case Uri = 'uri';

    /**
     * A string of comma-separated values
     */
    case CommaSeparated = 'comma-separated';

    /**
     * A string of semicolon-separated values
     */
    case SemiColonSeparated = 'semicolon-separated';

    /**
     * A string of values separated by a space
     */
    case SpaceSeparated ='space-separated';

    /**
     * A string without HTML markup. BR and P tags are converted to line breaks. Other markup is stripped.
     */
    case PlainText ='plain-text';

    /**
     * No format
     */
    case None = '';
}
