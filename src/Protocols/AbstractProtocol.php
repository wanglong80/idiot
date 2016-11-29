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

abstract class AbstractProtocol
{
    const DEFAULT_DUBBO_VERSION = '2.8.4';

    abstract public function connect($host, $port, $path, $method, $args, $group, $version, $dubboVersion);
}