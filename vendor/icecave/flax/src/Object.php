<?php
namespace Icecave\Flax;

/**
 * A wrapper around an object that allows you to overide the class name used during Hessian serialization.
 */
class Object
{
    /**
     * @param string $className The class name to use for serialization.
     * @param object $object    The actual object to serialize.
     */
    public function __construct($className, $object)
    {
        $this->className = $className;
        $this->object = $object;
    }

    /**
     * Get the class name to use for serialization.
     *
     * @return string The class name to use for serialization.
     */
    public function className()
    {
        return $this->className;
    }

    /**
     * Get the internal obejct.
     *
     * @return string The internal object.
     */
    public function object()
    {
        return $this->object;
    }

    private $className;
    private $object;
}
