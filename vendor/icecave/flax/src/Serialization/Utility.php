<?php
namespace Icecave\Flax\Serialization;

abstract class Utility
{
    /**
     * @return boolean
     */
    public static function isBigEndian()
    {
        return pack('S', 0x1020) === pack('n', 0x1020);
    }

    /**
     * @param string $buffer
     *
     * @return string
     */
    public static function convertEndianness($buffer)
    {
        return self::isBigEndian()
            ? $buffer
            : strrev($buffer);
    }

    /**
     * @param integer $value
     *
     * @return string
     */
    public static function packInt64($value)
    {
        $hi = (0xffffffff00000000 & $value) >> 32;
        $lo = (0x00000000ffffffff & $value);

        return pack('NN', $hi, $lo);
    }

    /**
     * @param string $bytes
     *
     * @return integer
     */
    public static function unpackInt64($bytes)
    {
        list(, $hi, $lo) = unpack('N2', $bytes);

        return ($hi << 32) | $lo;
    }

    /**
     * @param integer $byte
     *
     * @return integer
     */
    public static function byteToUnsigned($byte)
    {
        list(, $value) = unpack('C', pack('c', $byte));

        return $value;
    }

    /**
     * @param integer $byte
     *
     * @return integer
     */
    public static function byteToSigned($byte)
    {
        list(, $value) = unpack('c', pack('C', $byte));

        return $value;
    }
}
