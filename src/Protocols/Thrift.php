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
use Thrift\Transport\TSocket;
use Thrift\Transport\TBufferedTransport;
use Thrift\Exception\TException;
use Thrift\ClassLoader\ThriftClassLoader;
use Idiot\TProtocol\TBinaryDubboProtocol;


class Thrift extends AbstractProtocol
{
    const MAGIC = 0xdabc ;
    const VERSION = 1;
    const NAME="thrift";
    private $loader ;

    public function __construct(){
      $this->loader = new ThriftClassLoader();
      $this->loader->registerDefinition("com\\xintiaotime\\thrift\\demo", "../idl/gen-php/");
      $this->loader->register();
    }

    public function connect($host, $port, $path, $method, $args, $group, $version, $dubboVersion = self::DEFAULT_DUBBO_VERSION)
    {
        //TODO  fix 
      $socket = new TSocket($host, $port);
		  $transport = new TBufferedTransport($socket, 1024, 1024);
  		$protocol = new TBinaryDubboProtocol($transport);
  		
      $transport->open();
      $protocol->writeI16(self::MAGIC);
      $protocol->writeI32(1000);
      $protocol->writeI16(1000);
      $protocol->writeByte(self::VERSION);
      $protocol->writeString($path);
      $protocol->writeI64(random_int(0,9999));
      $transport->flush();

      $className = "\\".str_replace("." ,"\\",$path) . 'Client';
      $client = new $className($protocol);

      var_dump($client);

      $data = call_user_func(array($client, $method), $args);

      return $data;
    }
}
