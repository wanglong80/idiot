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
namespace Idiot\Languages;

use Exception;
use Idiot\Type;

class Java extends AbstractLanguage
{
    private $typeRefsMap = [
        Type::SHORT => 'S',
        Type::INT => 'I',
        Type::LONG => 'J',
        Type::FLOAT => 'F',
        Type::DOUBLE => 'D',
        Type::BOOLEAN => 'Z',
        Type::STRING => 'Ljava/lang/String;'
    ];

    public function typeRef($type)
    {
        return (strpos($type, '.') === FALSE ? $this->typeRefsMap[$type] : 'L' . str_replace('.', '/', $type) . ';');
    }
}