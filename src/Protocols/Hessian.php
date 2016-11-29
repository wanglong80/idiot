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
namespace Idiot\Protocols;

use Exception;
use Icecave\Flax\Serialization\Encoder;
use Icecave\Flax\Serialization\Decoder;
use Icecave\Flax\HessianClientFactory;

class Hessian extends AbstractProtocol
{
    public function connect($host, $port, $path, $method, $args, $group, $version, $dubboVersion = self::DEFAULT_DUBBO_VERSION)
    {
        $client = new HessianClientFactory;
        list($data, $error) = $client->create("{$host}:{$port}")->doRequest($method, $args);
        return $data;
    }
}