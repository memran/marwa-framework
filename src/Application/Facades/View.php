<?php
/**
 * @author    Mohammad Emran <memran.dhk@gmail.com>
 * @copyright 2018
 *
 * @see https://www.github.com/memran
 * @see http://www.memran.me
 */

namespace Marwa\Application\Facades;
use Marwa\Application\Facades\Facade;

class View extends Facade
{
    /**
     * [getClassAlias description] return class aliase name
     *
     * @return [type] [description]
     */
    public static function getClassAlias(): string
    {
          return 'view';
    }

}
