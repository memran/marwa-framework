<?php
/**
 *
 * @author    Mohammad Emran <memran.dhk@gmail.com>
 * @copyright 2018
 *
 * @see https://www.github.com/memran
 * @see http://www.memran.me
 */

namespace Marwa\Application\Commands\BasicCommands;

use Marwa\Application\Commands\AbstractCommand;
use Marwa\Application\Utils\Random;

class KeyGenerate extends AbstractCommand
{
    /**
     * [$name description]
     *
     * @var string
     */
    var $name = "key:generate";
    /**
     * [$description description]
     *
     * @var string
     */
    var $description = "Application Key Generate";
    /**
     * [$help description]
     *
     * @var string
     */
    var $help = "key:generate will help you to set Application Key.";


    /**
     * [handle description] this will execute and print sql results on table
     *
     * @return [type] [description]
     */
    public function handle(): void
    {
        $keyStr = Random::generate(32);
        $this->info("Application Key is " . $keyStr);
    }

}