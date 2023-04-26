<?php declare(strict_types=1);
/**
 * @author    Mohammad Emran <memran.dhk@gmail.com>
 * @copyright 2018
 *
 * @see https://www.github.com/memran
 * @see http://www.memran.me
 */

namespace Marwa\Application\Jobs;
use React\EventLoop\Factory;

trait JobTrait
{

    /**
     * [$loop description]
     *
     * @var null
     */
    var $loop=null;

    /**
     * [createFactoryLoop description]
     *
     * @return [type] [description]
     */
    public function createFactoryLoop() : void
    {
        if(is_null($this->loop)) {
            $this->loop = Factory::create();
        }

    }

    /**
     * [getFactoryLoop description]
     *
     * @return [type] [description]
     */
    public function getFactoryLoop()
    {
        return $this->loop;
    }

}
