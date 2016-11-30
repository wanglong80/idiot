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

use stdClass;
use Exception;
use Idiot\Adapter;
use Idiot\Type;
use Idiot\Utility;
use Icecave\Flax\Serialization\Encoder;
use Icecave\Flax\Serialization\Decoder;
use Icecave\Collections\Vector;
use Icecave\Chrono\DateTime;

class Dubbo extends AbstractProtocol
{
    const DEFAULT_LANGUAGE = 'Java';

    public function connect($host, $port, $path, $method, $args, $group, $version, $dubboVersion = self::DEFAULT_DUBBO_VERSION)
    {
        try
        {
            $socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
            socket_connect($socket, $host, $port);

            $buffer = $this->buffer($path, $method, $args, $group, $version, $dubboVersion);
            socket_write($socket, $buffer, strlen($buffer));

            $data = '';
            $bl = 16;

            do
            {
                $chunk = @socket_read($socket, 1024);

                if (empty($data))
                {
                    $arr = Utility::sliceToArray($chunk, 0, 16);
                    $i = 0;
                    
                    while ($i < 3)
                    {
                        $bl += array_pop($arr) * pow(256, $i++);
                    }
                }

                $data .= $chunk;

                if (empty($chunk) || strlen($data) >= $bl)
                {
                    break;
                }
            }
            while(TRUE);

            socket_close($socket);
            
            return $this->parser($data);
        }
        catch(Exception $e)
        {
            $message = $data ? $this->rinser($data) : $e->getMessage();
            throw new Exception($message);
        }
    }

    private function rinser($data)
    {
        return substr($data, 17);
    }

    private function parser($data)
    {
        $decoder = new Decoder;
        $decoder->feed($this->rinser($data));
        $obj = $decoder->finalize();
        return $this->recursive($obj);
    }

    private function recursive($data)
    {
        if ($data instanceof Vector)
        {            
            return $this->recursive($data->elements());
        }

        if ($data instanceof DateTime)
        {
            return $data->unixTime();
        }
        
        if ($data instanceof stdClass)
        {
            foreach ($data as $key => $value)
            {
                $data->$key = $this->recursive($value);
            }
        }

        if (is_array($data))
        {
            foreach ($data as $key => $value)
            {
                $data[$key] = $this->recursive($value);
            }
        }

        return $data;
    }

    private function buffer($path, $method, $args, $group, $version, $dubboVersion)
    {
        $typeRefs = $this->typeRefs($args);

        $attachment = Type::object('java.util.HashMap', [
            'interface' => $path,
            'version' => $version,
            'group' => $group,
            'path' => $path,
            'timeout' => '60000'
        ]);

        $bufferBody = $this->bufferBody($path, $method, $typeRefs, $args, $attachment, $version, $dubboVersion);
        $bufferHead = $this->bufferHead(strlen($bufferBody));
        return $bufferHead . $bufferBody;
    }

    private function bufferHead($length)
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

        return Utility::asciiArrayToString($head);
    }

    private function bufferBody($path, $method, $typeRefs, $args, $attachment, $version, $dubboVersion)
    {
        $body = '';
        $encoder = new Encoder;
        $body .= $encoder->encode($dubboVersion);
        $body .= $encoder->encode($path);
        $body .= $encoder->encode($version);
        $body .= $encoder->encode($method);
        $body .= $encoder->encode($typeRefs);
        
        foreach ($args as $arg)
        {
            $body .= $encoder->encode($arg);
        }

        $body .= $encoder->encode($attachment);
        return $body;
    }

    private function typeRefs(&$args)
    {
        $typeRefs = '';

        if (count($args))
        {
            $lang = Adapter::language(self::DEFAULT_LANGUAGE);

            foreach ($args as &$arg)
            {
                if ($arg instanceof Type)
                {
                    $type = $arg->type;
                    $arg = $arg->value;
                }
                else
                {
                    $type = $this->argToType($arg);             
                }

                $typeRefs .= $lang->typeRef($type);
            }
        }  

        return $typeRefs;
    }

    private function argToType($arg)
    {
        switch(gettype($arg))
        {
            case 'integer': 
                return $this->numToType($arg);
            case 'boolean': 
                return Type::BOOLEAN;
            case 'double': 
                return Type::DOUBLE;
            case 'string':
                return Type::STRING;
            case 'object': 
                return $arg->className();
            default:
                throw new Exception("Handler for type {$arg} not implemented");
        }
    }

    private function numToType($value)
    {
        if (Utility::isBetween($value, -32768, 32767))
        {
            return Type::SHORT;
        } 
        elseif (Utility::isBetween($value, -2147483648, 2147483647))
        {
            return Type::INT;
        } 

        return Type::LONG;
    }
}