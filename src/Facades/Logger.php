<?php

/**
 * @author    Mohammad Emran <memran.dhk@gmail.com>
 * @copyright 2018
 *
 * @see https://www.github.com/memran
 * @see http://www.memran.me
 */

namespace Marwa\App\Facades;

use Marwa\App\Facades\Facade;

final class Logger extends Facade
{
    /**
     * [getClassAlias description]
     *
     * @return string
     */
    public static function getClassAlias(): string
    {
        return 'logger';
    }
}
