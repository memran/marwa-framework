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

/**
 * @method static valid( $username, $password )
 */
class Auth extends Facade
{
      /**
       * [getClassAlias description]
       *
       * @return string
       */
    public static function getClassAlias()
    {
        return 'auth';
    }

}
