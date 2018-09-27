<?php
namespace Icecave\Flax\Exception;

use Exception;
use Icecave\Collections\Map;

abstract class AbstractHessianFaultException extends Exception
{
    /**
     * @param Map            $properties Properties of the fault.
     * @param Exception|null $previous   The previous exception, if any.
     */
    public function __construct(Map $properties, Exception $previous = null)
    {
        $this->properties = $properties;

        parent::__construct(
            $properties->getWithDefault('message', 'Hessian fault.'),
            0,
            $previous
        );
    }

    /**
     * @return Map Properties of the fault.
     */
    public function properties()
    {
        return $this->properties;
    }

    private $properties;
}
