<?php

namespace Connector\Exceptions;

/**
 * Indicates that the operation execution has been aborted
 * due to a system error or a user-configured abort condition.
 * Connector may not continue execution of any remaining operations.
 */
class AbortedOperationException extends \Exception
{

}
