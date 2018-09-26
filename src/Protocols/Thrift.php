<?php
/**
 * Idiot
 *  - Dubbo Client in Zookeeper.
 *
 * Licensed under the Massachusetts Institute of Technology
 *
 * For full copyright and license information, please see the LICENSE file
 * Redistributions of files must retain the above copyright notice.
 *
 * dubbo-thrift protocol implement 
 * <pre>
 * |<-                                  message header                                  ->|<- message body ->|
 * +----------------+----------------------+------------------+---------------------------+------------------+
 * | magic (2 bytes)|message size (4 bytes)|head size(2 bytes)| version (1 byte) | header |   message body   |
 * +----------------+----------------------+------------------+---------------------------+------------------+
 * |<-                                               message size                                          ->|
 * </pre>
 *
 * @author   Shi Liu  < liushi5216@gmail.com >
 * @link     https://github.com/lornewang/idiot
 */
 
namespace Idiot\Protocols;
use Exception;

class Thrift extends AbstractProtocol
{
    public function connect($host, $port, $path, $method, $args, $group, $version, $dubboVersion = self::DEFAULT_DUBBO_VERSION)
    {
       //TODO  fix 
       
        return $data;
    }
}
