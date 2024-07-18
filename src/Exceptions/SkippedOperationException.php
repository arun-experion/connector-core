<?php

namespace Connector\Exceptions;

/**
 * Indicates that the operation's execution has been skipped
 * due to a user-configured skip condition. Connector execution
 * may continue with remaining non-dependent operations.
 */
class SkippedOperationException extends \Exception
{

}
