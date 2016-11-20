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

class Adapter
{
    private static $instances = [];

    /**
     * Load adapter instance
     *
     * @param  string $dir
     * @param  string $class
     * @return object
     */
    public static function load($dir, $class)
    {
        if (isset(self::$instances[$dir . $class]))
        {
            return self::$instances[$dir . $class];
        }

        $class = ucfirst($class);
        $class = __NAMESPACE__ . "\\{$dir}\\{$class}";

        if ( ! class_exists($class))
        {
            throw new Exception("Can not match the class according to adapter {$class}");
        }
        
        return (self::$instances[$dir . $class] = new $class);
    }

    /**
     * Load adapter instance of language
     *
     * @param  string $class
     * @return object
     */
    public static function language($class)
    {
        return self::load('Languages', $class);
    }

    /**
     * Load adapter instance of protocol
     *
     * @param  string $class
     * @return object
     */
    public static function protocol($class)
    {
        return self::load('Protocols', $class);
    }
}