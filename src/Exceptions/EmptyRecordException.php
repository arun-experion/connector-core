<?php

namespace Connector\Exceptions;

/**
 * Indicates that an Operation failed after finding only an empty record to load.
 * Connector execution may continue with remaining non-dependent operations.
 */
class EmptyRecordException extends \Exception
{

}
