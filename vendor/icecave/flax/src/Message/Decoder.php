<?php
namespace Icecave\Flax\Message;

use Icecave\Flax\Exception\DecodeException;
use Icecave\Flax\Serialization\Decoder as SerializationDecoder;

/**
 * Streaming Hessian decoder.
 */
class Decoder
{
    public function __construct()
    {
        $this->serializationDecoder = new SerializationDecoder();

        $this->reset();
    }

    /**
     * Reset the decoder.
     *
     * Clears all internal state and allows the decoder to decode a new value.
     */
    public function reset()
    {
        $this->serializationDecoder->reset();
        $this->state = DecoderState::BEGIN();
        $this->buffer = '';
        $this->value = null;
    }

    /**
     * Feed Hessian data to the decoder.
     *
     * The buffer may contain an incomplete Hessian message.
     *
     * @param string $buffer The hessian message to decode.
     */
    public function feed($buffer)
    {
        $length = strlen($buffer);

        for ($index = 0; $index < $length; ++$index) {
            list(, $byte) = unpack('C', $buffer[$index]);
            $this->feedByte($byte);
        }
    }

    /**
     * Attempt to finalize decoding.
     *
     * @param mixed &$value Assigned the the decoded message.
     *
     * @return boolean True if the decoder has received a complete message, otherwise false.
     */
    public function tryFinalize(&$value = null)
    {
        if (DecoderState::COMPLETE() !== $this->state) {
            return false;
        }

        $value = $this->value;
        $this->reset();

        return true;
    }

    /**
     * Finalize decoding and return the decoded message.
     *
     * @return mixed           The decoded message.
     * @throws DecodeException If the decoder has not yet received a full Hessian message.
     */
    public function finalize()
    {
        if ($this->tryFinalize($value)) {
            return $value;
        }

        throw new DecodeException('Unexpected end of stream (state: ' . $this->state . ').');
    }

    /**
     * Feed a single byte of Hessian data to the decoder.
     *
     * This is the main point-of-entry to the parser, responsible for delegating to the individual methods
     * responsible for handling each decoder state ($this->handleXXX()).
     *
     * @param integer $byte
     *
     * @throws DecodeException if the given byte can not be decoded in the current state.
     */
    private function feedByte($byte)
    {
        switch ($this->state) {
            case DecoderState::VERSION():
                return $this->handleVersion($byte);
            case DecoderState::MESSAGE_TYPE():
                return $this->handleMessageType($byte);
            case DecoderState::RPC_REPLY():
                return $this->handleValue($byte);
            case DecoderState::RPC_FAULT():
                return $this->handleValue($byte);
            case DecoderState::COMPLETE():
                throw new DecodeException('Decoder has not been reset.');
        }

        if (HessianConstants::HEADER !== $byte) {
            throw new DecodeException('Invalid byte at start of message: 0x' . dechex($byte) . ' (state: ' . $this->state . ').');
        }

        $this->state = DecoderState::VERSION();
    }

    /**
     * Handle decoding the Hessian version.
     *
     * @param integer $byte
     */
    private function handleVersion($byte)
    {
        $this->buffer .= pack('c', $byte);

        if (strlen(HessianConstants::VERSION) > strlen($this->buffer)) {
            return;
        } elseif ($this->buffer === HessianConstants::VERSION) {
            $this->buffer = '';
            $this->state = DecoderState::MESSAGE_TYPE();
        } else {
            throw new DecodeException('Unsupported Hessian version: 0x' . bin2hex($this->buffer) . '.');
        }
    }

    /**
     * Handle decoding the message type.
     *
     * @param integer $byte
     */
    private function handleMessageType($byte)
    {
        if (HessianConstants::MESSAGE_TYPE_REPLY === $byte) {
            $this->state = DecoderState::RPC_REPLY();
        } elseif (HessianConstants::MESSAGE_TYPE_FAULT === $byte) {
            $this->state = DecoderState::RPC_FAULT();
        } else {
            throw new DecodeException('Unsupported message type: 0x' . dechex($byte) . '.');
        }
    }

    /**
     * Handle decoding an RPC reply.
     *
     * @param integer $byte
     */
    private function handleValue($byte)
    {
        $this->serializationDecoder->feed(pack('c', $byte));

        $value = null;
        if ($this->serializationDecoder->tryFinalize($value)) {
            $this->value = [
                $this->state === DecoderState::RPC_REPLY(),
                $value,
            ];

            $this->state = DecoderState::COMPLETE();
        }
    }

    private $serializationDecoder;
    private $state;
    private $buffer;
    private $value;
}
