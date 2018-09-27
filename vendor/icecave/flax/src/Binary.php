<?php
namespace Icecave\Flax;

/**
 * A wrapper around string that forces the Hessian encoder to treat it as a binary value.
 */
class Binary
{
    /**
     * @param string $data The binary data.
     */
    public function __construct($data = '')
    {
        $this->data = $data;
    }

    /**
     * Get the binary data.
     *
     * @return string The binary data.
     */
    public function data()
    {
        return $this->data;
    }

    private $data;
}
