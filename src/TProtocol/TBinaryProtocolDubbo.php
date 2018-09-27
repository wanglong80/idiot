<?php
/*
 * Licensed to the Apache Software Foundation (ASF) under one
 * or more contributor license agreements. See the NOTICE file
 * distributed with this work for additional information
 * regarding copyright ownership. The ASF licenses this file
 * to you under the Apache License, Version 2.0 (the
 * "License"); you may not use this file except in compliance
 * with the License. You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing,
 * software distributed under the License is distributed on an
 * "AS IS" BASIS, WITHOUT WARRANTIES OR CONDITIONS OF ANY
 * KIND, either express or implied. See the License for the
 * specific language governing permissions and limitations
 * under the License.
 *
 * @package thrift.protocol
 */

namespace Idiot\TProtocol;


/**
 * Accelerated binary protocol: used in conjunction with the thrift_protocol
 * extension for faster deserialization
 */
class TBinaryDubboProtocol extends TBinaryProtocol
{

    const MAGIC = 0xdabc ;
    const VERSION = 1;
    const NAME="thrift";
    
    public function readMessageBegin(&$name, &$type, &$seqid)
    {

      $this->readI16($unused);
      $this->readI32($unused);
      $this->readI16($unused);
      $this->readByte($unused);
      $this->readString($unsed);
      $this->readI64($unused);

      parent->readMessageBegin($name, $type, $seqid);
    }

    public function writeMessageBegin(&$name, &$type, &$seqid)
    {

      $protocol->writeI16(self::MAGIC);
      $protocol->writeI32(PHP_INT_SIZE);
      $protocol->writeI16(PHP_INT_SIZE);
      $protocol->writeByte(self::VERSION);
      $protocol->writeString($path);
      $protocol->writeI64(random_int());

      parent->readMessageBegin($name, $type, $seqid);
    }
}
