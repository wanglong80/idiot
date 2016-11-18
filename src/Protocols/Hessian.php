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
use Idiot\Utilities;
use Idiot\Object;
use Icecave\Flax\Serialization\Encoder;
use Icecave\Flax\Serialization\Decoder;

class Hessian extends AbstractProtocol
{
    private $typeRef = [
        'boolean' => 'Z',
        'int' => 'I',
        'short' => 'S',
        'long' => 'J',
        'double' => 'D',
        'float' => 'F'
    ];

    public function parser($data)
    {
        $data = substr($data, 17);
        $decoder = new Decoder;
        $decoder->feed($data);
        return $decoder->finalize();
    }

    public function buffer($path, $method, $args, $group, $version, $dubboVersion = self::DEFAULT_DUBBO_VERSION)
    {
        if (count($args) > 0)
        {
            $types = '';
            foreach ($args as $arg)
            {
                $type = '';
                switch(gettype($arg))
                {
                    case 'integer': $type = Utilities::integerToTypeString($arg); break;
                    case 'boolean': $type = 'boolean'; break;
                    case 'double': $type = 'double'; break;
                    case 'string': $type ='java.lang.String';break;
                    case 'object': $type = $arg->className(); break;
                    default: throw new Exception("Handler for type {$type} not implemented");
                }

                $types .= (strpos($type, '.') === FALSE 
                    ? $this->typeRef[$type] 
                    : 'L' . str_replace('.', '/', $type) . ';');
            }
        }

        $attachment = new Object('java.util.HashMap', [
            'interface' => $path,
            'version' => $version,
            'group' => $group,
            'path' => $path,
            'timeout' => '60000'
        ]);

        $bufferBody = $this->bufferBody($path, $method, $types, $args, $attachment, $version, $dubboVersion);
        $bufferHead = $this->bufferHead(strlen($bufferBody));
        return $bufferHead . $bufferBody;
    }

    public function bufferHead($length)
    {
        $head = [0xda, 0xbb, 0xc2, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0];
        $i = 15;

        if ($length - 256 < 0)
        {
            $head[$i] = $length - 256;
        }
        else
        {
            while ($length - 256 > 0)
            {
                $head[$i--] = $length % 256;
                $length = $length >> 8;
            }

            $head[$i] = $length;
        }

        return Utilities::asciiArrayToString($head);
    }

    public function bufferBody($path, $method, $types, $args, $attachment, $version, $dubboVersion)
    {
        $body = '';
        $encoder = new Encoder;
        $body .= $encoder->encode($dubboVersion);
        $body .= $encoder->encode($path);
        $body .= $encoder->encode($version);
        $body .= $encoder->encode($method);
        $body .= $encoder->encode($types);
        
        foreach ($args as $arg)
        {
            $body .= $encoder->encode($arg);
        }

        $body .= $encoder->encode($attachment);
        return $body;
    }
}