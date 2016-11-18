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

class Connection
{
    private $socket;

    /**
     * Via socket connect to dubbo service
     *
     * @param  string  $host
     * @param  integer $port
     * @return void
     */
    public function connect($host, $port)
    {
        $this->socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        socket_connect($this->socket, $host, $port);
    }

    /**
     * Fetch remote data
     *
     * @param  string $buffer
     * @return string
     */
    public function fetch($buffer)
    {
        socket_write($this->socket, $buffer, strlen($buffer));

        $data = '';
        if (FALSE !== ($bytes = socket_read($this->socket, 1024))) {
            $data .= $bytes;
        }

        socket_close($this->socket);

        return $data;
    }
}