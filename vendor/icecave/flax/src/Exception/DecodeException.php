<?php
namespace Icecave\Flax\Exception;

use Exception;

/**
 * Indicates an error while decoding a serialized Hessian value.
 */
class DecodeException extends Exception
{
    /**
     * @param string         $message  The exception message.
     * @param Exception|null $previous The previous exception, if any.
     */
    public function __construct($message, Exception $previous = null)
    {
        parent::__construct($message, 0, $previous);
    }
}
