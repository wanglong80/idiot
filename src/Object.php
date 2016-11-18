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

class Object extends \Icecave\Flax\Object
{
    // Inherited from Flax Object.
    public function __construct($class, $properties)
    {
        $value = new stdClass;

        foreach ($properties as $key => $val)
        {
            $value->$key = $val;
        }

        parent::__construct($class, $value);
    }
}