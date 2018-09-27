<?php
namespace Icecave\Flax\Message;

use Icecave\Flax\Serialization\Encoder as SerializationEncoder;

class Encoder
{
    public function __construct()
    {
        $this->serializationEncoder = new SerializationEncoder();
    }

    public function reset()
    {
        $this->serializationEncoder->reset();
    }

    /**
     * @return string
     */
    public function encodeVersion()
    {
        return pack('c', HessianConstants::HEADER) . HessianConstants::VERSION;
    }

    /**
     * @param string $methodName
     * @param array  $arguments
     *
     * @return string
     */
    public function encodeCall($methodName, array $arguments)
    {
        $buffer  = pack('c', HessianConstants::MESSAGE_TYPE_CALL);
        $buffer .= $this->serializationEncoder->encode($methodName);
        $buffer .= $this->serializationEncoder->encode(count($arguments));

        foreach ($arguments as $value) {
            $buffer .= $this->serializationEncoder->encode($value);
        }

        return $buffer;
    }

    private $serializationEncoder;
}
