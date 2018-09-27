<?php
namespace Icecave\Flax\Serialization;

use Icecave\Chrono\DateTime;
use Icecave\Collections\Map;
use Icecave\Collections\Stack;
use Icecave\Collections\Vector;
use Icecave\Flax\Binary;
use Icecave\Flax\Exception\DecodeException;
use stdClass;

/**
 * Streaming Hessian decoder.
 */
class Decoder
{
    public function __construct()
    {
        $this->stack = new Stack();
        $this->classDefinitions = new Vector();
        $this->references = new Vector();

        $this->reset();
    }

    /**
     * Reset the decoder.
     *
     * Clears all internal state and allows the decoder to decode a new value.
     */
    public function reset()
    {
        $this->stack->clear();
        $this->classDefinitions->clear();
        $this->references->clear();
        $this->currentContext = null;
        $this->value = null;

        $this->pushState(DecoderState::BEGIN());
    }

    /**
     * Feed Hessian data to the decoder.
     *
     * The buffer may contain an incomplete Hessian value.
     *
     * @param string $buffer The hessian data to decode.
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
     * @param mixed &$value Assigned the the decoded value.
     *
     * @return boolean True if the decoder has received a complete value, otherwise false.
     */
    public function tryFinalize(&$value = null)
    {
        if ($this->currentContext->state !== DecoderState::COMPLETE()) {
            return false;
        }

        $value = $this->value;
        $this->reset();

        return true;
    }

    /**
     * Finalize decoding and return the decoded value.
     *
     * @return mixed           The decoded value.
     * @throws DecodeException If the decoder has not yet received a full Hessian value.
     */
    public function finalize()
    {
        if ($this->tryFinalize($value)) {
            return $value;
        }

        throw new DecodeException('Unexpected end of stream (state: ' . $this->currentContext->state . ').');
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
        switch ($this->currentContext->state) {
            case DecoderState::STRING_SIZE():
                return $this->handleStringSize($byte);
            case DecoderState::STRING_DATA():
                return $this->handleStringData($byte);
            case DecoderState::STRING_CHUNK_SIZE():
                return $this->handleStringSize($byte);
            case DecoderState::STRING_CHUNK_FINAL_SIZE():
                return $this->handleStringSize($byte);
            case DecoderState::STRING_CHUNK_CONTINUATION():
                return $this->handleStringChunkContinuation($byte);
            case DecoderState::BINARY_SIZE():
                return $this->handleBinarySize($byte);
            case DecoderState::BINARY_DATA():
                return $this->handleBinaryData($byte);
            case DecoderState::BINARY_CHUNK_SIZE():
                return $this->handleBinarySize($byte);
            case DecoderState::BINARY_CHUNK_FINAL_SIZE():
                return $this->handleBinarySize($byte);
            case DecoderState::BINARY_CHUNK_CONTINUATION():
                return $this->handleBinaryChunkContinuation($byte);
            case DecoderState::INT32():
                return $this->handleInt32($byte);
            case DecoderState::INT64():
                return $this->handleInt64($byte);
            case DecoderState::DOUBLE_1():
                return $this->handleDouble1($byte);
            case DecoderState::DOUBLE_2():
                return $this->handleDouble2($byte);
            case DecoderState::DOUBLE_4():
                return $this->handleDouble4($byte);
            case DecoderState::DOUBLE_8():
                return $this->handleDouble8($byte);
            case DecoderState::TIMESTAMP_MILLISECONDS():
                return $this->handleTimestampMilliseconds($byte);
            case DecoderState::TIMESTAMP_MINUTES():
                return $this->handleTimestampMinutes($byte);
            case DecoderState::COLLECTION_TYPE():
                return $this->handleCollectionType($byte);
            case DecoderState::VECTOR():
            case DecoderState::MAP_KEY():
                return $this->handleNextCollectionElement($byte);
            case DecoderState::VECTOR_SIZE():
            case DecoderState::REFERENCE():
            case DecoderState::CLASS_DEFINITION_SIZE():
            case DecoderState::OBJECT_INSTANCE_TYPE():
                return $this->handleBeginInt32Strict($byte);
            case DecoderState::CLASS_DEFINITION_NAME():
            case DecoderState::CLASS_DEFINITION_FIELD():
                return $this->handleBeginStringStrict($byte);
            case DecoderState::COMPLETE():
                throw new DecodeException('Decoder has not been reset.');
        }

         if (!$this->handleBegin($byte)) {
            throw new DecodeException('Invalid byte at start of value: 0x' . dechex($byte) . ' (state: ' . $this->currentContext->state . ').');
         }
    }

    /**
     * Emit a decoded value.
     *
     * Depending on the current state the value emitted will be used if different ways.
     *
     * If the decoder has reached the end of the value being decoded the emitted value is stored
     * for retreival by {@see ValueDecoder::finalize()};
     *
     * @param mixed $value
     */
    private function emitValue($value)
    {
        switch ($this->currentContext->state) {
            case DecoderState::STRING():
                return $this->popStateAndEmitValue($value);
            case DecoderState::STRING_CHUNK_SIZE():
                return $this->emitStringChunkSize($value);
            case DecoderState::STRING_SIZE():
                return $this->emitStringChunkFinalSize($value);
            case DecoderState::STRING_CHUNK_FINAL_SIZE():
                return $this->emitStringChunkFinalSize($value);
            case DecoderState::STRING_CHUNK_CONTINUATION():
                return $this->emitStringChunkContinuation($value);
            case DecoderState::BINARY():
                return $this->emitBinary($value);
            case DecoderState::BINARY_CHUNK_SIZE():
                return $this->emitBinaryChunkSize($value);
            case DecoderState::BINARY_SIZE():
            case DecoderState::BINARY_CHUNK_FINAL_SIZE():
                return $this->emitBinaryChunkFinalSize($value);
            case DecoderState::BINARY_CHUNK_CONTINUATION():
                return $this->emitBinaryChunkContinuation($value);
            case DecoderState::COLLECTION_TYPE():
                return $this->emitCollectionType($value);
            case DecoderState::VECTOR():
                return $this->emitVectorElement($value);
            case DecoderState::VECTOR_SIZE():
                return $this->emitFixedVectorSize($value);
            case DecoderState::VECTOR_FIXED():
                return $this->emitFixedVectorElement($value);
            case DecoderState::MAP_KEY():
                return $this->emitMapKey($value);
            case DecoderState::MAP_VALUE():
                return $this->emitMapValue($value);
            case DecoderState::CLASS_DEFINITION_NAME():
                return $this->emitClassDefinitionName($value);
            case DecoderState::CLASS_DEFINITION_SIZE():
                return $this->emitClassDefinitionSize($value);
            case DecoderState::CLASS_DEFINITION_FIELD():
                return $this->emitClassDefinitionField($value);
            case DecoderState::OBJECT_INSTANCE_TYPE():
                return $this->emitObjectInstanceType($value);
            case DecoderState::OBJECT_INSTANCE_FIELD():
                return $this->emitObjectInstanceField($value);
            case DecoderState::REFERENCE():
                return $this->emitReference($value);
        }

        $this->value = $value;
        $this->setState(DecoderState::COMPLETE());
    }

    /**
     * @param string $value
     */
    public function emitStringChunkSize($value)
    {
        $this->popState();
        $this->currentContext->result .= $value;
        $this->setState(DecoderState::STRING_CHUNK_CONTINUATION());
    }

    /**
     * @param string $value
     */
    public function emitStringChunkFinalSize($value)
    {
        $this->popState();
        $this->setState(DecoderState::STRING());
        $this->emitValue($this->currentContext->result . $value);
    }

    /**
     * @param string $value
     */
    public function emitStringChunkContinuation($value)
    {
        $this->setState(DecoderState::STRING());
        $this->emitValue($this->currentContext->result . $value);
    }

    /**
     * @param string $value
     */
    public function emitBinary($value)
    {
        $this->popStateAndEmitValue(new Binary($value));
    }

    /**
     * @param string $value
     */
    public function emitBinaryChunkSize($value)
    {
        $this->popState();
        $this->currentContext->result .= $value;
        $this->setState(DecoderState::BINARY_CHUNK_CONTINUATION());
    }

    /**
     * @param string $value
     */
    public function emitBinaryChunkFinalSize($value)
    {
        $this->popState();
        $this->setState(DecoderState::BINARY());
        $this->emitValue($this->currentContext->result . $value);
    }

    /**
     * @param string $value
     */
    public function emitBinaryChunkContinuation($value)
    {
        $this->setState(DecoderState::BINARY());
        $this->emitValue($this->currentContext->result . $value);
    }

    /**
     * Emit a collection type.
     *
     * As PHP does not have typed collections the collection type is currently ignored.
     *
     * @param string|integer $value The type name or index.
     */
    private function emitCollectionType($value)
    {
        $this->popState(); // discard the type

        if (0 === $this->currentContext->expectedSize) {
            $this->popStateAndEmitResult();
        }
    }

    /**
     * Emit the element in a vector.
     *
     * @param mixed $value The vector element.
     */
    private function emitVectorElement($value)
    {
        $this->currentContext->result->pushBack($value);
    }

    /**
     * Emit the size of a fixed-length vector.
     *
     * @param integer $value The size of the vector.
     */
    private function emitFixedVectorSize($value)
    {
        $this->popState();

        if (0 === $value) {
            $this->popStateAndEmitValue(new Vector());
        } else {
            $this->currentContext->expectedSize = $value;
        }
    }

    /**
     * Emit an element in a fixed-length vector.
     *
     * @param mixed $value The vector element.
     */
    private function emitFixedVectorElement($value)
    {
        $this->currentContext->result->pushBack($value);

        if ($this->currentContext->result->size() === $this->currentContext->expectedSize) {
            $this->popStateAndEmitResult();
        }
    }

    /**
     * Emit the key of the next element in a map.
     *
     * @param mixed $value The key of the next element in the map.
     */
    private function emitMapKey($value)
    {
        $this->currentContext->nextKey = $value;
        $this->setState(DecoderState::MAP_VALUE());
    }

    /**
     * Emit the value of the next element in a map.
     *
     * @param mixed $value The value of the next element in the map.
     */
    private function emitMapValue($value)
    {
        $this->currentContext->result->set(
            $this->currentContext->nextKey,
            $value
        );

        $this->currentContext->nextKey = null;
        $this->setState(DecoderState::MAP_KEY());
    }

    /**
     * Emit the name of a class definition.
     *
     * @param string $value The name of the class.
     */
    private function emitClassDefinitionName($value)
    {
        $this->currentContext->result->name = $value;
        $this->setState(DecoderState::CLASS_DEFINITION_SIZE());
    }

    /**
     * Emit the number of fields in a class definition.
     *
     * @param integer $value THe number of fields in the class.
     */
    private function emitClassDefinitionSize($value)
    {
        $this->currentContext->expectedSize = $value;

        if (0 === $value) {
            $this->popState();
        } else {
            $this->setState(DecoderState::CLASS_DEFINITION_FIELD());
        }
    }

    /**
     * Emit a field name in a class definition.
     *
     * @param string $value The name of the field.
     */
    private function emitClassDefinitionField($value)
    {
        $classDef = $this->currentContext->result;

        $classDef->fields->pushBack($value);

        if ($classDef->fields->size() === $this->currentContext->expectedSize) {
            $this->popState();
        }
    }

    /**
     * Emit type (class definition index) of an object instance.
     *
     * @param integer $value The index of the class definition.
     */
    private function emitObjectInstanceType($value)
    {
        $this->popState();
        $this->startObjectInstance($value);
    }

    /**
     * Emit the value of an object instance field.
     *
     * @param mixed $value The value of the object field.
     */
    private function emitObjectInstanceField($value)
    {
        $fields = $this->currentContext->definition->fields;
        $fieldName = $fields[$this->currentContext->nextKey++];
        $this->currentContext->result->{$fieldName} = $value;

        if ($fields->size() === $this->currentContext->nextKey) {
            $this->popStateAndEmitResult();
        }
    }

    /**
     * Emit a value reference.
     *
     * @param integer $value The index of the referenced object.
     */
    private function emitReference($value)
    {
        $this->popStateAndEmitValue(
            $this->references[$value]
        );
    }

    /**
     * @param integer $byte
     */
    private function startCompactString($byte)
    {
        if (HessianConstants::STRING_COMPACT_START === $byte) {
            $this->emitValue('');
        } else {
            $this->pushState(DecoderState::STRING_DATA());
            $this->currentContext->expectedSize = $byte - HessianConstants::STRING_COMPACT_START;
        }
    }

    /**
     * @param integer $byte
     */
    private function startString($byte)
    {
        $this->pushState(DecoderState::STRING_SIZE());
        $this->currentContext->buffer .= pack('c', $byte - HessianConstants::STRING_START);
    }

    /**
     * @param integer $byte
     */
    private function startCompactBinary($byte)
    {
        if (HessianConstants::BINARY_COMPACT_START === $byte) {
            $this->emitValue('');
        } else {
            $this->pushState(DecoderState::BINARY_DATA());
            $this->currentContext->expectedSize = $byte - HessianConstants::BINARY_COMPACT_START;
        }
    }

    /**
     * @param integer $byte
     */
    private function startBinary($byte)
    {
        $this->pushState(DecoderState::BINARY_SIZE());
        $this->currentContext->buffer .= pack('c', $byte - HessianConstants::BINARY_START);
    }

    /**
     * @param integer $byte
     */
    private function startInt32Compact2($byte)
    {
        $this->pushState(DecoderState::INT32());
        $value = $byte - HessianConstants::INT32_2_OFFSET;
        $this->currentContext->buffer = pack('ccc', $value >> 16, $value >> 8, $value);
    }

    /**
     * @param integer $byte
     */
    private function startInt32Compact3($byte)
    {
        $this->pushState(DecoderState::INT32());
        $value = $byte - HessianConstants::INT32_3_OFFSET;
        $this->currentContext->buffer = pack('cc', $value >> 8, $value);
    }

    /**
     * @param integer $byte
     */
    private function startInt64Compact2($byte)
    {
        $this->pushState(DecoderState::INT32());
        $value = $byte - HessianConstants::INT64_2_OFFSET;
        $this->currentContext->buffer = pack('ccc', $value >> 16, $value >> 8, $value);
    }

    /**
     * @param integer $byte
     */
    private function startInt64Compact3($byte)
    {
        $this->pushState(DecoderState::INT32());
        $value = $byte - HessianConstants::INT64_3_OFFSET;
        $this->currentContext->buffer = pack('cc', $value >> 8, $value);
    }

    private function startFixedLengthVector()
    {
        $this->pushState(DecoderState::VECTOR_FIXED(), new Vector());
        $this->pushState(DecoderState::VECTOR_SIZE());
    }

    private function startTypedVector()
    {
        $this->pushState(DecoderState::VECTOR(), new Vector());
        $this->pushState(DecoderState::COLLECTION_TYPE());
    }

    private function startTypedFixedLengthVector()
    {
        $this->pushState(DecoderState::VECTOR_FIXED(), new Vector());
        $this->pushState(DecoderState::VECTOR_SIZE());
        $this->pushState(DecoderState::COLLECTION_TYPE());
    }

    /**
     * @param integer $byte
     */
    private function startCompactTypedFixedLengthVector($byte)
    {
        $this->pushState(DecoderState::VECTOR_FIXED(), new Vector());
        $this->currentContext->expectedSize = $byte - HessianConstants::VECTOR_TYPED_FIXED_COMPACT_START;

        $this->pushState(DecoderState::COLLECTION_TYPE());
    }

    /**
     * @param integer $byte
     */
    private function startCompactFixedLengthVector($byte)
    {
        if (HessianConstants::VECTOR_FIXED_COMPACT_START === $byte) {
            $this->emitValue(new Vector());
        } else {
            $this->pushState(DecoderState::VECTOR_FIXED(), new Vector());
            $this->currentContext->expectedSize = $byte - HessianConstants::VECTOR_FIXED_COMPACT_START;
        }
    }

    private function startTypedMap()
    {
        $this->pushState(DecoderState::MAP_KEY(), new Map());
        $this->pushState(DecoderState::COLLECTION_TYPE());
    }

    private function startClassDefinition()
    {
        $this->pushState(DecoderState::CLASS_DEFINITION_NAME());

        $def = new stdClass();
        $def->name = null;
        $def->fields = new Vector();

        $this->currentContext->result = $def;

        $this->classDefinitions->pushBack($def);
    }

    /**
     * @param integer $classDefIndex
     */
    private function startObjectInstance($classDefIndex)
    {
        $classDef = $this->classDefinitions[$classDefIndex];

        if ($classDef->fields->isEmpty()) {
            $this->emitValue(new stdClass());
        } else {
            $this->pushState(DecoderState::OBJECT_INSTANCE_FIELD(), new stdClass());
            $this->currentContext->definition = $classDef;
            $this->currentContext->nextKey = 0;
        }
    }

    /**
     * @param integer $byte
     */
    private function startCompactObjectInstance($byte)
    {
        $this->startObjectInstance($byte - HessianConstants::OBJECT_INSTANCE_COMPACT_START);
    }

    /**
     * Handle the start of a new value.
     *
     * @param integer $byte
     *
     * @return boolean True if the given byte is valid as the first byte of a Hessian value; otherwise, false.
     */
    private function handleBegin($byte)
    {
        return $this->handleBeginScalar($byte)
            || $this->handleBeginInt32($byte, false)
            || $this->handleBeginInt64($byte)
            || $this->handleBeginDouble($byte)
            || $this->handleBeginString($byte, false)
            || $this->handleBeginBinary($byte)
            || $this->handleBeginTimestamp($byte)
            || $this->handleBeginVector($byte)
            || $this->handleBeginMap($byte)
            || $this->handleBeginObject($byte)
            ;
    }

    /**
     * Handle the start of a scalar (null, true, false) value.
     *
     * @param integer $byte
     *
     * @return boolean True if the given byte represents true, false or null; otherwise, false.
     */
    private function handleBeginScalar($byte)
    {
        if (HessianConstants::NULL_VALUE === $byte) {
            $this->emitValue(null);
        } elseif (HessianConstants::BOOLEAN_TRUE === $byte) {
            $this->emitValue(true);
        } elseif (HessianConstants::BOOLEAN_FALSE === $byte) {
            $this->emitValue(false);
        } else {
            return false;
        }

        return true;
    }

    /**
     * Handle the start of a string value.
     *
     * @param integer $byte
     *
     * @return boolean True if the given byte is valid as the first byte of a Hessian string; otherwise, false.
     */
    private function handleBeginString($byte)
    {
        if (HessianConstants::STRING_CHUNK === $byte) {
            $this->pushState(DecoderState::STRING());
            $this->pushState(DecoderState::STRING_CHUNK_SIZE());
        } elseif (HessianConstants::STRING_CHUNK_FINAL === $byte) {
            $this->pushState(DecoderState::STRING());
            $this->pushState(DecoderState::STRING_CHUNK_FINAL_SIZE());
        } elseif (HessianConstants::STRING_COMPACT_START <= $byte && $byte <= HessianConstants::STRING_COMPACT_END) {
            $this->pushState(DecoderState::STRING());
            $this->startCompactString($byte);
        } elseif (HessianConstants::STRING_START <= $byte && $byte <= HessianConstants::STRING_END) {
            $this->pushState(DecoderState::STRING());
            $this->startString($byte);
        } else {
            return false;
        }

        return true;
    }

    /**
     * Handle the start of a string value, throwing if unable to do so.
     *
     * @param integer $byte
     *
     * @throws DecodeException if the given byte is valid as the first byte of a Hessian string.
     */
    private function handleBeginStringStrict($byte)
    {
        if (!$this->handleBeginString($byte)) {
            throw new DecodeException('Invalid byte at start of string: 0x' . dechex($byte) . ' (state: ' . $this->currentContext->state . ').');
        }
    }

    /**
     * Handle the start of a binary value.
     *
     * @param integer $byte
     *
     * @return boolean True if the given byte is valid as the first byte of a Hessian binary buffer; otherwise, false.
     */
    private function handleBeginBinary($byte)
    {
        if (HessianConstants::BINARY_CHUNK === $byte) {
            $this->pushState(DecoderState::BINARY());
            $this->pushState(DecoderState::BINARY_CHUNK_SIZE());
        } elseif (HessianConstants::BINARY_CHUNK_FINAL === $byte) {
            $this->pushState(DecoderState::BINARY());
            $this->pushState(DecoderState::BINARY_CHUNK_FINAL_SIZE());
        } elseif (HessianConstants::BINARY_COMPACT_START <= $byte && $byte <= HessianConstants::BINARY_COMPACT_END) {
            $this->pushState(DecoderState::BINARY());
            $this->startCompactBinary($byte);
        } elseif (HessianConstants::BINARY_START <= $byte && $byte <= HessianConstants::BINARY_END) {
            $this->pushState(DecoderState::BINARY());
            $this->startBinary($byte);
        } else {
            return false;
        }

        return true;
    }

    /**
     * Handle the start of a 32-bit integer value.
     *
     * @param integer $byte
     *
     * @return boolean True if the given byte is valid as the first byte of a Hessian 32-bit integer; otherwise, false.
     */
    private function handleBeginInt32($byte)
    {
        if (HessianConstants::INT32_4 === $byte) {
            $this->pushState(DecoderState::INT32());
        } elseif (HessianConstants::INT32_1_START <= $byte && $byte <= HessianConstants::INT32_1_END) {
            $this->emitValue($byte - HessianConstants::INT32_1_OFFSET);
        } elseif (HessianConstants::INT32_2_START <= $byte && $byte <= HessianConstants::INT32_2_END) {
            $this->startInt32Compact2($byte);
        } elseif (HessianConstants::INT32_3_START <= $byte && $byte <= HessianConstants::INT32_3_END) {
            $this->startInt32Compact3($byte);
        } else {
            return false;
        }

        return true;
    }

    /**
     * Handle the start of a 32-bit integer value, throwing if unable to do so.
     *
     * @param integer $byte
     *
     * @throws Exception\DecodeEception if the given byte is valid as the first byte of a Hessian 32-bit integer.
     */
    private function handleBeginInt32Strict($byte)
    {
        if (!$this->handleBeginInt32($byte)) {
            throw new DecodeException('Invalid byte at start of int: 0x' . dechex($byte) . ' (state: ' . $this->currentContext->state . ').');
        }
    }

    /**
     * Handle the start of a 64-bit integer value.
     *
     * @param integer $byte
     *
     * @return boolean True if the given byte is valid as the first byte of a Hessian 64-bit integer; otherwise, false.
     */
    private function handleBeginInt64($byte)
    {
        if (HessianConstants::INT64_4 === $byte) {
            $this->pushState(DecoderState::INT32());
        } elseif (HessianConstants::INT64_8 === $byte) {
            $this->pushState(DecoderState::INT64());
        } elseif (HessianConstants::INT64_1_START <= $byte && $byte <= HessianConstants::INT64_1_END) {
            $this->emitValue($byte - HessianConstants::INT64_1_OFFSET);
        } elseif (HessianConstants::INT64_2_START <= $byte && $byte <= HessianConstants::INT64_2_END) {
            $this->startInt64Compact2($byte);
        } elseif (HessianConstants::INT64_3_START <= $byte && $byte <= HessianConstants::INT64_3_END) {
            $this->startInt64Compact3($byte);
        } else {
            return false;
        }

        return true;
    }

    /**
     * Handle the start of a double precision floating point value.
     *
     * @param integer $byte
     *
     * @return boolean True if the given byte is valid as the first byte of a Hessian double; otherwise, false.
     */
    private function handleBeginDouble($byte)
    {
        if (HessianConstants::DOUBLE_ZERO === $byte) {
            $this->emitValue(0.0);
        } elseif (HessianConstants::DOUBLE_ONE === $byte) {
            $this->emitValue(1.0);
        } elseif (HessianConstants::DOUBLE_1 === $byte) {
            $this->pushState(DecoderState::DOUBLE_1());
        } elseif (HessianConstants::DOUBLE_2 === $byte) {
            $this->pushState(DecoderState::DOUBLE_2());
        } elseif (HessianConstants::DOUBLE_4 === $byte) {
            $this->pushState(DecoderState::DOUBLE_4());
        } elseif (HessianConstants::DOUBLE_8 === $byte) {
            $this->pushState(DecoderState::DOUBLE_8());
        } else {
            return false;
        }

        return true;
    }

    /**
     * Handle the start of timestamp value.
     *
     * @param integer $byte
     *
     * @return boolean True if the given byte is valid as the first byte of a Hessian timestamp; otherwise, false.
     */
    private function handleBeginTimestamp($byte)
    {
        if (HessianConstants::TIMESTAMP_MILLISECONDS === $byte) {
            $this->pushState(DecoderState::TIMESTAMP_MILLISECONDS());
        } elseif (HessianConstants::TIMESTAMP_MINUTES === $byte) {
            $this->pushState(DecoderState::TIMESTAMP_MINUTES());
        } else {
            return false;
        }

        return true;
    }

    /**
     * Handle the start of a vector.
     *
     * @param integer $byte
     *
     * @return boolean True if the given byte is valid as the first byte of a Hessian vector; otherwise, false.
     */
    private function handleBeginVector($byte)
    {
        if (HessianConstants::VECTOR_TYPED === $byte) {
            $this->startTypedVector();
        } elseif (HessianConstants::VECTOR_TYPED_FIXED === $byte) {
            $this->startTypedFixedLengthVector();
        } elseif (HessianConstants::VECTOR === $byte) {
            $this->pushState(DecoderState::VECTOR(), new Vector());
        } elseif (HessianConstants::VECTOR_FIXED === $byte) {
            $this->startFixedLengthVector();
        } elseif (HessianConstants::VECTOR_TYPED_FIXED_COMPACT_START <= $byte && $byte <= HessianConstants::VECTOR_TYPED_FIXED_COMPACT_END) {
            $this->startCompactTypedFixedLengthVector($byte);
        } elseif (HessianConstants::VECTOR_FIXED_COMPACT_START <= $byte && $byte <= HessianConstants::VECTOR_FIXED_COMPACT_END) {
            $this->startCompactFixedLengthVector($byte);
        } else {
            return false;
        }

        return true;
    }

    /**
     * Handle the start of a map.
     *
     * @param integer $byte
     *
     * @return boolean True if the given byte is valid as the first byte of a Hessian map; otherwise, false.
     */
    private function handleBeginMap($byte)
    {
        if (HessianConstants::MAP_TYPED === $byte) {
            $this->startTypedMap();
        } elseif (HessianConstants::MAP === $byte) {
            $this->pushState(DecoderState::MAP_KEY(), new Map());
        } else {
            return false;
        }

        return true;
    }

    /**
     * Handle the start of an object.
     *
     * @param integer $byte
     *
     * @return boolean True if the given byte is valid as the first byte of a Hessian object; otherwise, false.
     */
    private function handleBeginObject($byte)
    {
        if (HessianConstants::OBJECT_INSTANCE === $byte) {
            $this->pushState(DecoderState::OBJECT_INSTANCE_TYPE());
        } elseif (HessianConstants::CLASS_DEFINITION === $byte) {
            $this->startClassDefinition();
        } elseif (HessianConstants::REFERENCE === $byte) {
            $this->pushState(DecoderState::REFERENCE());
        } elseif (HessianConstants::OBJECT_INSTANCE_COMPACT_START <= $byte && $byte <= HessianConstants::OBJECT_INSTANCE_COMPACT_END) {
            $this->startCompactObjectInstance($byte);
        } else {
            return false;
        }

        return true;
    }

    /**
     * Handle decoding a 16-bit (short) string size.
     *
     * @param integer $byte
     */
    private function handleStringSize($byte)
    {
        $this->currentContext->expectedSize = $this->appendInt16Data($byte, false);

        if (0 === $this->currentContext->expectedSize) {
            $this->popStateAndEmitValue('');
        } elseif (null !== $this->currentContext->expectedSize) {
            $size = $this->currentContext->expectedSize;
            $this->pushState(DecoderState::STRING_DATA());
            $this->currentContext->expectedSize = $size;
        }
    }

    /**
     * Handle decoding UTF-8 string data.
     *
     * @param integer $byte
     */
    private function handleStringData($byte)
    {
        if ($this->appendStringData($byte)) {
            $this->popStateAndEmitBuffer();
        }
    }

    /**
     * Handle decoding the first byte after a string chunk.
     *
     * @param integer $byte
     */
    public function handleStringChunkContinuation($byte)
    {
        if (HessianConstants::STRING_CHUNK === $byte) {
            $this->pushState(DecoderState::STRING_CHUNK_SIZE());
        } elseif (HessianConstants::STRING_CHUNK_FINAL === $byte) {
            $this->pushState(DecoderState::STRING_CHUNK_FINAL_SIZE());
        } elseif (HessianConstants::STRING_COMPACT_START <= $byte && $byte <= HessianConstants::STRING_COMPACT_END) {
            $this->startCompactString($byte);
        } elseif (HessianConstants::STRING_START <= $byte && $byte <= HessianConstants::STRING_END) {
            $this->startString($byte);
        } else {
            throw new DecodeException('Invalid byte at start of string chunk: 0x' . dechex($byte) . ' (state: ' . $this->currentContext->state . ').');
        }
    }

    /**
     * Handle decoding a 16-bit (short) binary buffer size.
     *
     * @param integer $byte
     */
    private function handleBinarySize($byte)
    {
        $this->currentContext->expectedSize = $this->appendInt16Data($byte, false);

        if (0 === $this->currentContext->expectedSize) {
            $this->popStateAndEmitValue('');
        } elseif (null !== $this->currentContext->expectedSize) {
            $size = $this->currentContext->expectedSize;
            $this->pushState(DecoderState::BINARY_DATA());
            $this->currentContext->expectedSize = $size;
        }
    }

    /**
     * Handle decoding a binary buffer.
     *
     * @param integer $byte
     */
    private function handleBinaryData($byte)
    {
        if ($this->appendBinaryData($byte)) {
            $this->popStateAndEmitBuffer();
        }
    }

    /**
     * Handle the start of a binary value.
     *
     * @param integer $byte
     */
    private function handleBinaryChunkContinuation($byte)
    {
        if (HessianConstants::BINARY_CHUNK === $byte) {
            $this->pushState(DecoderState::BINARY_CHUNK_SIZE());
        } elseif (HessianConstants::BINARY_CHUNK_FINAL === $byte) {
            $this->pushState(DecoderState::BINARY_CHUNK_FINAL_SIZE());
        } elseif (HessianConstants::BINARY_COMPACT_START <= $byte && $byte <= HessianConstants::BINARY_COMPACT_END) {
            $this->startCompactBinary($byte);
        } elseif (HessianConstants::BINARY_START <= $byte && $byte <= HessianConstants::BINARY_END) {
            $this->startBinary($byte);
        } else {
            throw new DecodeException('Invalid byte at start of binary chunk: 0x' . dechex($byte) . ' (state: ' . $this->currentContext->state . ').');
        }
    }

    /**
     * Handle decoding a double encoded in 1 octet.
     *
     * @param integer $byte
     */
    private function handleDouble1($byte)
    {
        $this->popStateAndEmitValue(
            floatval(Utility::byteToSigned($byte))
        );
    }

    /**
     * Handle decoding a double encoded in 2 octets.
     *
     * @param integer $byte
     */
    private function handleDouble2($byte)
    {
        $value = $this->appendInt16Data($byte);

        if (null !== $value) {
            $this->popStateAndEmitValue(floatval($value));
        }
    }

    /**
     * Handle decoding a double encoded as an IEEE float (4 octets).
     *
     * @param integer $byte
     */
    private function handleDouble4($byte)
    {
        $this->currentContext->buffer .= pack('C', $byte);

        if (4 === strlen($this->currentContext->buffer)) {
            list(, $value) = unpack(
                'l',
                Utility::convertEndianness($this->currentContext->buffer)
            );

            $this->popStateAndEmitValue($value * 0.001);
        }
    }

    /**
     * Handle decoding a double.
     *
     * @param integer $byte
     */
    private function handleDouble8($byte)
    {
        $this->currentContext->buffer .= pack('C', $byte);

        if (8 === strlen($this->currentContext->buffer)) {
            list(, $value) = unpack(
                'd',
                Utility::convertEndianness($this->currentContext->buffer)
            );
            $this->popStateAndEmitValue($value);
        }
    }

    /**
     * Handle decoding a 32-bit integer.
     *
     * @param integer $byte
     */
    public function handleInt32($byte)
    {
        $value = $this->appendInt32Data($byte);

        if (null !== $value) {
            $this->popStateAndEmitValue($value);
        }
    }

    /**
     * Handle decoding a 64-bit integer.
     *
     * @param integer $byte
     */
    public function handleInt64($byte)
    {
        $value = $this->appendInt64Data($byte);

        if (null !== $value) {
            $this->popStateAndEmitValue($value);
        }
    }

    /**
     * Handle decoding a timestamp in milliseconds.
     *
     * @param integer $byte
     */
    public function handleTimestampMilliseconds($byte)
    {
        $value = $this->appendInt64Data($byte);

        if (null !== $value) {
            $this->popStateAndEmitValue(DateTime::fromUnixTime($value / 1000));
        }
    }

    /**
     * Handle decoding a timestamp in minutes.
     *
     * @param integer $byte
     */
    public function handleTimestampMinutes($byte)
    {
        $value = $this->appendInt32Data($byte);

        if (null !== $value) {
            $this->popStateAndEmitValue(DateTime::fromUnixTime($value * 60));
        }
    }

    /**
     * Handle decoding the end of a variable length collection.
     *
     * @param integer $byte
     */
    public function handleNextCollectionElement($byte)
    {
        if (HessianConstants::COLLECTION_TERMINATOR === $byte) {
            $this->popStateAndEmitResult();
        } else {
            $this->handleBegin($byte);
        }
    }

    /**
     * Handle decoding a the type of a typed collection.
     *
     * @param integer $byte
     */
    public function handleCollectionType($byte)
    {
        if ($this->handleBeginString($byte)) {
            return;
        } elseif ($this->handleBeginInt32($byte)) {
            return;
        }

        throw new DecodeException('Invalid byte at start of collection type: 0x' . dechex($byte) . ' (state: ' . $this->currentContext->state . ').');
    }

    /**
     * Push a new state onto the stack.
     *
     * @param DecoderState $state
     * @param mixed        $result
     */
    private function pushState(DecoderState $state, $result = '')
    {
        $context = new stdClass();
        $context->state = $state;
        $context->buffer = '';
        $context->result = $result;
        $context->nextKey = null;
        $context->expectedSize = null;
        $context->definition = null;

        $this->stack->push($context);

        if (is_object($result)) {
            $this->references->pushBack($result);
        }

        $this->currentContext = $context;
    }

    /**
     * Set the current stack context.
     *
     * @param DecoderState $state
     */
    private function setState(DecoderState $state)
    {
        $this->currentContext->state = $state;
    }

    /**
     * Pop the current state off the stack.
     */
    private function popState()
    {
        $this->stack->pop();
        $this->currentContext = $this->stack->next();

    }

    /**
     * Pop the current state off the stack, and emit a value.
     *
     * @param mixed $value
     */
    private function popStateAndEmitValue($value)
    {
        $this->popState();
        $this->emitValue($value);
    }

    /**
     * Pop the current state off the stack, and emit the buffer.
     */
    private function popStateAndEmitBuffer()
    {
        $this->popStateAndEmitValue($this->currentContext->buffer);
    }

    /**
     * Pop the current state off the stack, and emit the result.
     */
    private function popStateAndEmitResult()
    {
        $this->popStateAndEmitValue($this->currentContext->result);
    }

    /**
     * Append UTF-8 string data to the internal buffer.
     *
     * @param integer $byte
     *
     * @return boolean True if the buffer contains a UTF-8 string of the expected length; otherwise, false.
     */
    private function appendStringData($byte)
    {
        $this->currentContext->buffer .= pack('C', $byte);

        // Check if we've even read enough bytes to possibly be complete ...
        if (strlen($this->currentContext->buffer) < $this->currentContext->expectedSize) {
            return false;
        // Check if we have a valid utf8 string ...
        } elseif (!mb_check_encoding($this->currentContext->buffer, 'utf8')) {
            return false;
        // Check if we've read the right number of multibyte characters ...
        } elseif (mb_strlen($this->currentContext->buffer, 'utf8') < $this->currentContext->expectedSize) {
            return false;
        }

        return true;
    }

    /**
     * Append binary data to the internal buffer.
     *
     * @param integer $byte
     *
     * @return boolean True if the buffer is of the expected length; otherwise, false.
     */
    private function appendBinaryData($byte)
    {
        $this->currentContext->buffer .= pack('C', $byte);

        if (strlen($this->currentContext->buffer) < $this->currentContext->expectedSize) {
            return false;
        }

        return true;
    }

    /**
     * Append 16-bit integer data to the internal buffer.
     *
     * @param integer $byte
     * @param boolean $signed
     *
     * @return integer|null Returns the decoded 16-bit integer; or null if the buffer does not yet contain enough data.
     */
    private function appendInt16Data($byte, $signed = true)
    {
        $this->currentContext->buffer .= pack('C', $byte);

        if (2 === strlen($this->currentContext->buffer)) {
            list(, $value) = unpack(
                $signed ? 's' : 'S',
                Utility::convertEndianness($this->currentContext->buffer)
            );

            return $value;
        }

        return null;
    }

    /**
     * Append 32-bit integer data to the internal buffer.
     *
     * @param integer $byte
     *
     * @return integer|null Returns the decoded 32-bit integer; or null if the buffer does not yet contain enough data.
     */
    private function appendInt32Data($byte)
    {
        $this->currentContext->buffer .= pack('C', $byte);

        if (4 === strlen($this->currentContext->buffer)) {
            list(, $value) = unpack(
                'l',
                Utility::convertEndianness($this->currentContext->buffer)
            );

            return $value;
        }

        return null;
    }

    /**
     * Append 64-bit integer data to the internal buffer.
     *
     * @param integer $byte
     *
     * @return integer|null Returns the decoded 64-bit integer; or null if the buffer does not yet contain enough data.
     */
    private function appendInt64Data($byte)
    {
        $this->currentContext->buffer .= pack('C', $byte);

        if (8 === strlen($this->currentContext->buffer)) {
            return Utility::unpackInt64($this->currentContext->buffer);
        }

        return null;
    }

    private $classDefinitions;
    private $objects;
    private $stack;
    private $currentContext;
    private $value;
}
