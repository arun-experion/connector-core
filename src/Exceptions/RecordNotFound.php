<?php

namespace Connector\Exceptions;

/**
 * Indicates that an Operation failed after not finding a record to extract.
 * Connector execution may continue with remaining non-dependent operations.
 *
 */
// TODO: Rename to RecordNotFoundException
class RecordNotFound extends \Exception
{

}
