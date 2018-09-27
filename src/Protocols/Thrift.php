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
 *
	思路：
	1、搞清楚dubbo的thrift加了哪些header，相比原生thrift
	2、给予idiot升级，增加一个protocol，并且继承thrift的protocol，实现dubbo的额外封装逻辑




 *
 * dubbo-thrift protocol implement 
 * <pre>
 * |<-                                  message header                                  ->|<- message body ->|
 * +----------------+----------------------+------------------+---------------------------+------------------+
 * | magic (2 bytes)|message size (4 bytes)|head size(2 bytes)| version (1 byte) | header |   message body   |
 * +----------------+----------------------+------------------+---------------------------+------------------+
 * |<-                                               message size                                          ->|
 * <p>
 * <b>header fields in version 1</b>
 * <ol>
 *     <li>string - service name</li>
 *     <li>long   - dubbo request id</li>  i64
 * </ol>
 * </p>
 * </pre>
 *
 * @author   Shi Liu  < liushi5216@gmail.com >
 * @link     https://github.com/lornewang/idiot
 */
 
namespace Idiot\Protocols;
use Exception;
use Thrift\Protocol\TBinaryProtocol;
use Thrift\Transport\TSocket;
use Thrift\Transport\TBufferedTransport;
use Thrift\Exception\TException;

class Thrift extends AbstractProtocol
{
    public function connect($host, $port, $path, $method, $args, $group, $version, $dubboVersion = self::DEFAULT_DUBBO_VERSION)
    {
        //TODO  fix 
      	$socket = new TSocket($host, $port);
		$transport = new TBufferedTransport($socket, 1024, 1024);
  		$protocol = new TBinaryProtocol($transport);
  		

        return $data;
    }
}
