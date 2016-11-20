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
 * @author      Lorne Wang < post@lorne.wang >
 * @copyright   Copyright (c) 2016 - 2017 , All rights reserved.
 * @link        https://github.com/lornewang/idiot
 */
namespace Idiot;

class Utility
{
    /**
     * ASCII array to string
     *
     * @param  string $asciis
     * @return string
     */
    public static function asciiArrayToString($asciis)
    {
        $chars = '';
        foreach ($asciis as $ascii)
        {
            $chars .= chr($ascii);
        }
        return $chars;
    }

    /**
     * Whether or not in between the two values
     *
     * @param  integer $value
     * @param  integer $min
     * @param  integer $max
     * @return boolean
     */
    public static function isBetween($value, $min, $max)
    {
		return $min <= $value && $value <= $max;
	}
}