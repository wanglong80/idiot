<?php
namespace Icecave\Flax\Serialization;

use DateTime;
use Icecave\Chrono\TimePointInterface;
use Icecave\Collections\AssociativeInterface;
use Icecave\Collections\Collection;
use Icecave\Collections\Map;
use Icecave\Collections\SequenceInterface;
use Icecave\Flax\Binary;
use Icecave\Flax\Exception\EncodeException;
use Icecave\Flax\Object;
use stdClass;

class Encoder
{
    public function __construct()
    {
        $this->nextReferenceId = 0;
        $this->references = new Map(
            null,
            function ($lhs, $rhs) {
                return strcmp(
                    spl_object_hash($lhs),
                    spl_object_hash($rhs)
                );
            }
        );
        $this->classDefinitions = new Map();
    }

    public function reset()
    {
        $this->nextReferenceId = 0;
        $this->references->clear();
        $this->classDefinitions->clear();
    }

    /**
     * @param mixed $value
     *
     * @return string
     * @throws EncodeException
     */
    public function encode($value)
    {
        $type = gettype($value);

        switch ($type) {
            case 'integer':
                return $this->encodeInteger($value);
            case 'boolean':
                return $this->encodeBoolean($value);
            case 'string':
                return $this->encodeString($value);
            case 'double':
                return $this->encodeDouble($value);
            case 'array':
                return $this->encodeArray($value);
            case 'object':
                return $this->encodeObject($value);
            case 'NULL':
                return $this->encodeNull();
        }

        throw new EncodeException('Can not encode value of type "' . $type . '".');
    }

    /**
     * @param string $value
     *
     * @return string
     */
    public function encodeBinary($value)
    {
        $length = strlen($value);

        if ($length <= HessianConstants::BINARY_COMPACT_LIMIT) {
            return pack('c', $length + HessianConstants::BINARY_COMPACT_START) . $value;
        } elseif ($length <= HessianConstants::BINARY_LIMIT) {
            return pack(
                'cc',
                ($length >> 8) + HessianConstants::BINARY_START,
                ($length)
            ) . $value;
        }

        $buffer = '';

        do {
            if ($length > HessianConstants::BINARY_CHUNK_SIZE) {
                $chunkLength = HessianConstants::BINARY_CHUNK_SIZE;
                $buffer .= pack('c', HessianConstants::BINARY_CHUNK);
            } else {
                $chunkLength = $length;
                $buffer .= pack('c', HessianConstants::BINARY_CHUNK_FINAL);
            }

            $buffer .= pack('n', $chunkLength);
            $buffer .= substr($value, 0, $chunkLength);

            $value = substr($value, $chunkLength);
            $length -= $chunkLength;

        } while ($length);

        return $buffer;
    }

    /**
     * @param integer $timestamp Number of milliseconds since unix epoch.
     *
     * @return string
     */
    public function encodeTimestamp($timestamp)
    {
        if ($timestamp % HessianConstants::TIMESTAMP_MILLISECONDS_PER_MINUTE) {
            return pack('c', HessianConstants::TIMESTAMP_MILLISECONDS) . Utility::packInt64($timestamp);
        } else {
            return pack(
                'cN',
                HessianConstants::TIMESTAMP_MINUTES,
                $timestamp / HessianConstants::TIMESTAMP_MILLISECONDS_PER_MINUTE
            );
        }
    }

    /**
     * @param integer $value
     *
     * @return string
     */
    private function encodeInteger($value)
    {
        // 1-byte ...
        if (HessianConstants::INT32_1_MIN <= $value && $value <= HessianConstants::INT32_1_MAX) {
            return pack('c', $value + HessianConstants::INT32_1_OFFSET);

        // 2-bytes ...
        } elseif (HessianConstants::INT32_2_MIN <= $value && $value <= HessianConstants::INT32_2_MAX) {
            return pack(
                'cc',
                ($value >> 8) + HessianConstants::INT32_2_OFFSET,
                ($value)
            );

        // 3-bytes ...
        } elseif (HessianConstants::INT32_3_MIN <= $value && $value <= HessianConstants::INT32_3_MAX) {
            return pack(
                'ccc',
                ($value >> 16) + HessianConstants::INT32_3_OFFSET,
                ($value >> 8),
                ($value)
            );

        // 4-bytes ...
        } elseif (HessianConstants::INT32_4_MIN <= $value && $value <= HessianConstants::INT32_4_MAX) {
            return pack('cN', HessianConstants::INT32_4, $value);
        }

        return pack('c', HessianConstants::INT64_8) . Utility::packInt64($value);
    }

    /**
     * @param boolean $value
     *
     * @return string
     */
    private function encodeBoolean($value)
    {
        return pack('c', $value ? HessianConstants::BOOLEAN_TRUE : HessianConstants::BOOLEAN_FALSE);
    }

    /**
     * @param string $value
     *
     * @return string
     */
    private function encodeString($value)
    {
        $length = mb_strlen($value, 'utf8');

        if ($length <= HessianConstants::STRING_COMPACT_LIMIT) {
            return pack('c', $length + HessianConstants::STRING_COMPACT_START) . $value;
        } elseif ($length <= HessianConstants::STRING_LIMIT) {
            return pack(
                'cc',
                ($length >> 8) + HessianConstants::STRING_START,
                ($length)
            ) . $value;
        }

        $buffer = '';

        do {
            if ($length > HessianConstants::STRING_CHUNK_SIZE) {
                $chunkLength = HessianConstants::STRING_CHUNK_SIZE;
                $buffer .= pack('c', HessianConstants::STRING_CHUNK);
            } else {
                $chunkLength = $length;
                $buffer .= pack('c', HessianConstants::STRING_CHUNK_FINAL);
            }

            $buffer .= pack('n', $chunkLength);
            $buffer .= mb_substr($value, 0, $chunkLength, 'utf8');

            // can not use default of 'null' for length in php 5.3
            $value = mb_substr($value, $chunkLength, $length, 'utf8');
            $length -= $chunkLength;

        } while ($length);

        return $buffer;
    }

    /**
     * @param double $value
     *
     * @return string
     */
    private function encodeDouble($value)
    {
        if (0.0 === $value) {
            return pack('c', HessianConstants::DOUBLE_ZERO);
        } elseif (1.0 === $value) {
            return pack('c', HessianConstants::DOUBLE_ONE);
        }

        $fraction = fmod($value, 1);

        if (0.0 == $fraction) {
            if (HessianConstants::DOUBLE_1_MIN <= $value && $value <= HessianConstants::DOUBLE_1_MAX) {
                return pack('cc', HessianConstants::DOUBLE_1, $value);
            } elseif (HessianConstants::DOUBLE_2_MIN <= $value && $value <= HessianConstants::DOUBLE_2_MAX) {
                return pack('cn', HessianConstants::DOUBLE_2, $value);
            }
        }

        $integer = $value * 1000;
        if (0.0 == fmod($integer, 1)) {
            return pack('c', HessianConstants::DOUBLE_4) . Utility::convertEndianness(pack('l', $integer));
        }

        return pack('c', HessianConstants::DOUBLE_8) . Utility::convertEndianness(pack('d', $value));
    }

    /**
     * @return string
     */
    private function encodeNull()
    {
        return pack('c', HessianConstants::NULL_VALUE);
    }

    /**
     * @param array $value
     *
     * @return string
     */
    private function encodeArray(array $value)
    {
        ++$this->nextReferenceId;

        if (Collection::isSequential($value)) {
            return $this->encodeVector($value);
        } else {
            return $this->encodeMap($value);
        }
    }

    /**
     * @param mixed<mixed> $collection
     *
     * @return string
     */
    private function encodeVector($collection)
    {
        $size = Collection::size($collection);

        if ($size <= HessianConstants::VECTOR_FIXED_COMPACT_LIMIT) {
            $buffer = pack('c', $size + HessianConstants::VECTOR_FIXED_COMPACT_START);
        } else {
            $buffer = pack('c', HessianConstants::VECTOR_FIXED) . $this->encodeInteger($size);
        }

        foreach ($collection as $element) {
            $buffer .= $this->encode($element);
        }

        return $buffer;
    }

    /**
     * @param mixed<mixed,mixed> $collection
     *
     * @return string
     */
    private function encodeMap($collection)
    {
        $buffer = pack('c', HessianConstants::MAP);

        foreach ($collection as $key => $value) {
            $buffer .= $this->encode($key);
            $buffer .= $this->encode($value);
        }

        $buffer .= pack('c', HessianConstants::COLLECTION_TERMINATOR);

        return $buffer;
    }

    /**
     * @param object $value
     *
     * @return string
     */
    private function encodeObject($value)
    {
        if ($value instanceof DateTime) {
            return $this->encodeTimestamp($value->getTimestamp() * 1000);
        } elseif ($value instanceof TimePointInterface) {
            return $this->encodeTimestamp($value->unixTime() * 1000);
        } elseif ($value instanceof Binary) {
            return $this->encodeBinary($value->data());
        }

        if ($value instanceof Object) {
            $className = $value->className();
            $value = $value->object();
        } else {
            $className = get_class($value);
        }

        $ref = null;
        if ($this->findReference($value, $ref)) {
            return $this->encodeReference($ref);
        } elseif ($value instanceof SequenceInterface) {
            return $this->encodeVector($value);
        } elseif ($value instanceof AssociativeInterface) {
            return $this->encodeMap($value);
        } elseif ('stdClass' === get_class($value)) {
            return $this->encodeStdClass($value, $className);
        }

        throw new EncodeException('Can not encode object of type "' . get_class($value) . '".');
    }

    /**
     * @param stdClass $value
     * @param string   $className
     *
     * @return string
     */
    private function encodeStdClass(stdClass $value, $className)
    {
        $sortedProperties = (array) $value;
        ksort($sortedProperties);

        $buffer = '';

        $defId = null;
        if (!$this->findClassDefinition($value, $defId)) {
            $buffer .= $this->encodeClassDefinition($className, array_keys($sortedProperties));
        }

        if ($defId <= HessianConstants::OBJECT_INSTANCE_COMPACT_LIMIT) {
            $buffer .= pack('c', $defId + HessianConstants::OBJECT_INSTANCE_COMPACT_START);
        } else {
            $buffer .= pack('c', HessianConstants::OBJECT_INSTANCE) . $this->encodeInteger($defId);
        }

        foreach ($sortedProperties as $value) {
            $buffer .= $this->encode($value);
        }

        return $buffer;
    }

    /**
     * @param mixed        $value
     * @param integer|null &$ref
     *
     * @return boolean
     */
    private function findReference($value, &$ref = null)
    {
        if ($this->references->tryGet($value, $ref)) {
            return true;
        }

        $this->references[$value] = $ref = $this->nextReferenceId++;

        return false;
    }

    /**
     * @param integer $ref
     *
     * @return string
     */
    private function encodeReference($ref)
    {
        return pack('c', HessianConstants::REFERENCE) . $this->encodeInteger($ref);
    }

    /**
     * @param stdClass     $value
     * @param integer|null &$defId
     *
     * @return boolean
     */
    private function findClassDefinition(stdClass $value, &$defId = null)
    {
        $key = $this->classDefinitionKey($value);

        if (!$this->classDefinitions->tryGet($key, $defId)) {
            $this->classDefinitions[$key] = $defId = $this->classDefinitions->size();

            return false;
        }

        return true;
    }

    /**
     * @param string        $className
     * @param array<string> $propertyNames
     *
     * @return string
     */
    private function encodeClassDefinition($className, array $propertyNames)
    {
        $buffer  = pack('c', HessianConstants::CLASS_DEFINITION) . $this->encodeString($className);
        $buffer .= $this->encodeInteger(count($propertyNames));

        foreach ($propertyNames as $name) {
            $buffer .= $this->encodeString($name);
        }

        return $buffer;
    }

    /**
     * @param stdClass $value
     *
     * @return string
     */
    private function classDefinitionKey(stdClass $value)
    {
        $properties = get_object_vars($value);
        ksort($properties);

        return implode(',', array_keys($properties));
    }

    private $nextReferenceId;
    private $references;
    private $classDefinitions;
}
