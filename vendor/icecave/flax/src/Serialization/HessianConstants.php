<?php
namespace Icecave\Flax\Serialization;

use Eloquent\Enumeration\AbstractEnumeration;

class HessianConstants extends AbstractEnumeration
{
    // x00 - x1f : utf-8 string length 0-32
    const STRING_COMPACT_START = 0x00;
    const STRING_COMPACT_END   = 0x1f;
    const STRING_COMPACT_LIMIT = 31;

    // x30 - x33 : utf-8 string length 0-1023
    const STRING_START = 0x30;
    const STRING_END   = 0x33;
    const STRING_LIMIT = 1023;

    // x52       : utf-8 string non-final chunk ('R')
    // x53       : utf-8 string final chunk ('S')
    const STRING_CHUNK       = 0x52;
    const STRING_CHUNK_FINAL = 0x53;
    const STRING_CHUNK_SIZE  = 0xffff;

    // x20 - x2f : binary data length 0-16
    const BINARY_COMPACT_START = 0x20;
    const BINARY_COMPACT_END   = 0x2f;
    const BINARY_COMPACT_LIMIT = 15;

    // x34 - x37 : binary data length 0-1023
    const BINARY_START = 0x34;
    const BINARY_END   = 0x37;
    const BINARY_LIMIT = 1023;

    // x41       : 8-bit binary data non-final chunk ('A')
    // x42       : 8-bit binary data final chunk ('B')
    const BINARY_CHUNK       = 0x41;
    const BINARY_CHUNK_FINAL = 0x42;
    const BINARY_CHUNK_SIZE  = 0xffff;

    // x80 - xbf : one-octet compact int (-x10 to x3f, x90 is 0)
    const INT32_1_START  = 0x80;
    const INT32_1_END    = 0xbf;
    const INT32_1_OFFSET = 0x90;
    const INT32_1_MIN    = -0x10;
    const INT32_1_MAX    = +0x2f;

    // xc0 - xcf : two-octet compact int (-x800 to x7ff)
    const INT32_2_START  = 0xc0;
    const INT32_2_END    = 0xcf;
    const INT32_2_OFFSET = 0xc8;
    const INT32_2_MIN    = -0x0800;
    const INT32_2_MAX    = +0x07ff;

    // xd0 - xd7 : three-octet compact int (-x40000 to x3ffff)
    const INT32_3_START  = 0xd0;
    const INT32_3_END    = 0xd7;
    const INT32_3_OFFSET = 0xd4;
    const INT32_3_MIN    = -0x040000;
    const INT32_3_MAX    = +0x03ffff;

    // x49       : 32-bit signed integer ('I')
    const INT32_4     = 0x49;
    const INT32_4_MIN = -0x80000000;
    const INT32_4_MAX = +0x7fffffff;

    // xd8 - xef : one-octet compact long (-x8 to xf, xe0 is 0)
    const INT64_1_START  = 0xd8;
    const INT64_1_END    = 0xef;
    const INT64_1_OFFSET = 0xe0;
    const INT64_1_MIN    = -0x08;
    const INT64_1_MAX    = +0x0f;

    // xf0 - xff : two-octet compact long (-x800 to x7ff, xf8 is 0)
    const INT64_2_START  = 0xf0;
    const INT64_2_END    = 0xff;
    const INT64_2_OFFSET = 0xf8;
    const INT64_2_MIN    = -0x0800;
    const INT64_2_MAX    = +0x07ff;

    // x38 - x3f : three-octet compact long (-x40000 to x3ffff)
    const INT64_3_START  = 0x38;
    const INT64_3_END    = 0x3f;
    const INT64_3_OFFSET = 0x3c;
    const INT64_3_MIN    = -0x040000;
    const INT64_3_MAX    = +0x03ffff;

    // x59       : long encoded as 32-bit int ('Y')
    // x4c       : 64-bit signed long integer ('L')
    const INT64_4       = 0x59;
    const INT64_8       = 0x4c;

    const INT64_HIGH_MASK = 0xffffffff00000000;
    const INT64_LOW_MASK  = 0x00000000ffffffff;

    // x5b       : double 0.0
    // x5c       : double 1.0
    const DOUBLE_ZERO = 0x5b;
    const DOUBLE_ONE  = 0x5c;

    // x5d       : double represented as byte (-128.0 to 127.0)
    const DOUBLE_1     = 0x5d;
    const DOUBLE_1_MIN = -128;
    const DOUBLE_1_MAX = +127;

    // x5e       : double represented as short (-32768.0 to 327676.0)
    const DOUBLE_2     = 0x5e;
    const DOUBLE_2_MIN = -32768;
    const DOUBLE_2_MAX = +32767;

    // x5f       : double represented as float
    const DOUBLE_4 = 0x5f;

    // x44       : 64-bit IEEE encoded double ('D')
    const DOUBLE_8 = 0x44;

    // x54       : boolean true ('T')
    // x46       : boolean false ('F')
    const BOOLEAN_TRUE  = 0x54;
    const BOOLEAN_FALSE = 0x46;

    // x4e       : null ('N')
    const NULL_VALUE = 0x4e;

    // x4a       : 64-bit UTC millisecond date
    // x4b       : 32-bit UTC minute date
    const TIMESTAMP_MILLISECONDS            = 0x4a;
    const TIMESTAMP_MINUTES                 = 0x4b;
    const TIMESTAMP_MILLISECONDS_PER_MINUTE = 60000;

    // x43       : object type definition ('C')
    // x4f       : object instance ('O')
    // x60 - x6f : object with direct type
    const CLASS_DEFINITION              = 0x43;
    const OBJECT_INSTANCE               = 0x4f;
    const OBJECT_INSTANCE_COMPACT_START = 0x60;
    const OBJECT_INSTANCE_COMPACT_END   = 0x6f;
    const OBJECT_INSTANCE_COMPACT_LIMIT = 15;

    // x51       : reference to map/list/object - integer ('Q')
    const REFERENCE = 0x51;

    // x55       : variable-length list/vector ('U')
    // x56       : fixed-length list/vector ('V')
    const VECTOR_TYPED       = 0x55;
    const VECTOR_TYPED_FIXED = 0x56;

    // x57       : variable-length untyped list/vector ('W')
    // x58       : fixed-length untyped list/vector ('X')
    const VECTOR       = 0x57;
    const VECTOR_FIXED = 0x58;

    // x70 - x77 : fixed list with direct length
    const VECTOR_TYPED_FIXED_COMPACT_START = 0x70;
    const VECTOR_TYPED_FIXED_COMPACT_END   = 0x77;
    const VECTOR_TYPED_FIXED_COMPACT_LIMIT = 7;

    // x78 - x7f : fixed untyped list with direct length
    const VECTOR_FIXED_COMPACT_START = 0x78;
    const VECTOR_FIXED_COMPACT_END   = 0x7f;
    const VECTOR_FIXED_COMPACT_LIMIT = 7;

    // x4d       : map with type ('M')
    // x48       : untyped map ('H')
    const MAP_TYPED = 0x4d;
    const MAP       = 0x48;

    // x5a       : list/map terminator ('Z')
    const COLLECTION_TERMINATOR = 0x5a;

    const RESERVED_40 = 0x40;
    const RESERVED_45 = 0x45;
    const RESERVED_47 = 0x47;
    const RESERVED_50 = 0x50;
}
