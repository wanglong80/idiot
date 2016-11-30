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
use Zookeeper as ZK;

class Zookeeper
{
    private $zk;

    public function __construct($conn)
    {
        $this->zk = new ZK;
        $this->zk->connect($conn);
    }

    /**
     * Get a dubbo provider uri
     *
     * @param  string $path
     * @param  string $version
     * @return string
     */
    public function getProvider($path, $version = '')
    {
        $providers = @$this->zk->getChildren("/dubbo/{$path}/providers");
        
        if (count($providers) < 1)
        {
            throw new Exception("Can not find the zoo: {$path} , please check dubbo service.");
        }

        foreach ($providers as $provider)
        {
            $info = parse_url(urldecode($provider));
            parse_str($info['query'], $args);
    
            if ($version && isset($args['version']) && $version == $args['version'])
            {
                break;
            }
        }

        return $provider;
    }
}