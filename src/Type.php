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

use stdClass;
use Icecave\Flax\Object;

class Type
{
    const SHORT = 1;
    const INT = 2;
    const INTEGER = 2;
    const LONG = 3;
    const FLOAT = 4;
    const DOUBLE = 5;
    const STRING = 6;
    const BOOL = 7;
    const BOOLEAN = 7;

    public function __construct($type, $value)
    {
        $this->type = $type;
        $this->value = $value;
    }

    /**
     * Short type
     *
     * @param  integer $value
     * @return Type
     */
    public static function short($value)
    {
        return new self(self::SHORT, $value);
    }

    /**
     * Int type
     *
     * @param  integer $value
     * @return Type
     */
    public static function int($value)
    {
        return new self(self::INT, $value);
    }

    /**
     * Integer type
     *
     * @param  integer $value
     * @return Type
     */
    public static function integer($value)
    {
        return new self(self::INTEGER, $value);
    }

    /**
     * Long type
     *
     * @param  integer $value
     * @return Type
     */
    public static function long($value)
    {
        return new self(self::LONG, $value);
    }

    /**
     * Float type
     *
     * @param  integer $value
     * @return Type
     */
    public static function float($value)
    {
        return new self(self::FLOAT, $value);
    }

    /**
     * Double type
     *
     * @param  integer $value
     * @return Type
     */
    public static function double($value)
    {
        return new self(self::DOUBLE, $value);
    }

    /**
     * String type
     *
     * @param  string $value
     * @return Type
     */
    public static function string($value)
    {
        return new self(self::STRING, $value);
    }

    /**
     * Bool type
     *
     * @param  boolean $value
     * @return Type
     */
    public static function bool($value)
    {
        return new self(self::BOOL, $value);
    }

    /**
     * Boolean type
     *
     * @param  boolean $value
     * @return Type
     */
    public static function boolean($value)
    {
        return new self(self::BOOLEAN, $value);
    }

    /**
     * Object type
     *
     * @param  integer $value
     * @return Object
     */
    public static function object($class, $properties)
    {
        $std = new stdClass;
        
        foreach ($properties as $key => $value)
        {
            $std->$key = ($value instanceof Type) ? $value->value : $value;
        }

        return new Object($class, $std);
    }
}