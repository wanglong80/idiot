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
 * @author   Lorne Wang < post@lorne.wang >
 * @link     https://github.com/lornewang/idiot
 */
namespace Idiot;

use Exception;

class Service extends Connection
{
    private $conn = '';
    private $host = '';
    private $port = '';
    private $path = '';
    private $group = '';
    private $version = '';
    private $dubboVersion = '2.8.4';
    private $protocol = 'hessian';
    private $driver;

    public function __construct($options)
    {
        foreach ($options as $key => $value)
        {
            if (property_exists($this, $key))
            {
                $this->$key = $value;
            }
        }

        if (empty($this->host) || empty($this->port))
        {
            $this->parseURItoProps(
                (new Zookeeper($this->conn))->getProvider($this->path)
            );
        }
    }

    /**
     * Calls to the remote interface
     *
     * @param  string $method
     * @param  array  $args
     * @return string
     */
    public function invoke($method, $args)
    {
        $this->connect($this->host, $this->port);

        $data = $this->fetch(
            $this->protocol()->buffer($this->path, $method, $args, $this->group, $this->version, $this->dubboVersion)
        );

        return $this->protocol()->parser($data);
    }

    /**
     * Get protocol instance
     *
     * @return object
     */
    public function protocol()
    {
        if (is_object($this->driver))
        {
            return $this->driver;
        }

        $class = strtoupper($this->protocol);
        $class = __NAMESPACE__ . "\Protocols\\{$class}";

        if ( ! class_exists($class))
        {
            throw new Exception("Can not match the class according to this protocol {$this->protocol}");
            
        }
        
        return ($this->driver = new $class);
    }

    /**
     * Parse the dubbo uri to this props
     *
     * @param  string $uri
     * @return void
     */
    public function parseURItoProps($uri)
    {
        $info = parse_url(urldecode($uri));
        parse_str($info['query'], $params);

        isset($info['host']) AND $this->host = $info['host'];
        isset($info['port']) AND $this->port = $info['port'];
        isset($params['version']) AND $this->version = $params['version'];
        isset($params['dubbo']) AND $this->dubboVersion = $params['dubbo'];
    }
}