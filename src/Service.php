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

class Service
{
    private $conn = '';
    private $host = '';
    private $port = '';
    private $path = '';
    private $group = '';
    private $version = '';
    private $dubboVersion = '2.8.4';
    private $protocol = 'dubbo';

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
                (new Zookeeper($this->conn))->getProvider($this->path, $this->version)
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
        $proto = Adapter::protocol($this->protocol);
 
        return $proto->connect(
            $this->host, 
            $this->port, 
            $this->path, 
            $method, 
            $args, 
            $this->group, 
            $this->version, 
            $this->dubboVersion
        );
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