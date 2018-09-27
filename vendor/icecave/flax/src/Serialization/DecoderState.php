<?php
namespace Icecave\Flax\Serialization;

use Eloquent\Enumeration\AbstractEnumeration;

class DecoderState extends AbstractEnumeration
{
    const BEGIN = 0;

    const STRING                    = 10;
    const STRING_SIZE               = 11;
    const STRING_DATA               = 12;
    const STRING_CHUNK_SIZE         = 13;
    const STRING_CHUNK_FINAL_SIZE   = 14;
    const STRING_CHUNK_CONTINUATION = 15;

    const BINARY                    = 20;
    const BINARY_SIZE               = 21;
    const BINARY_DATA               = 22;
    const BINARY_CHUNK_SIZE         = 23;
    const BINARY_CHUNK_FINAL_SIZE   = 24;
    const BINARY_CHUNK_CONTINUATION = 25;

    const DOUBLE_1 = 30;
    const DOUBLE_2 = 31;
    const DOUBLE_4 = 32;
    const DOUBLE_8 = 33;

    const INT32 = 43;
    const INT64 = 44;

    const TIMESTAMP_MILLISECONDS = 50;
    const TIMESTAMP_MINUTES      = 51;

    const COLLECTION_TYPE = 60;

    const VECTOR       = 70;
    const VECTOR_SIZE  = 71;
    const VECTOR_FIXED = 72;

    const MAP_KEY      = 80;
    const MAP_VALUE    = 81;

    const CLASS_DEFINITION_NAME  = 90;
    const CLASS_DEFINITION_SIZE  = 91;
    const CLASS_DEFINITION_FIELD = 92;

    const OBJECT_INSTANCE_TYPE   = 100;
    const OBJECT_INSTANCE_FIELD  = 101;

    const REFERENCE = 110;

    const COMPLETE = 120;
}
