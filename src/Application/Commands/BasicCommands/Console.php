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
use Psy\Shell;
use Marwa\Application\Commands\AbstractCommand;

class Console extends AbstractCommand
{
    /**
     * [$name description]
     *
     * @var string
     */
    var $name="console";
    /**
     * [$description description]
     *
     * @var string
     */
    var $description="CLI Debugger";
    /**
     * [$help description]
     *
     * @var string
     */
    var $help ="Debugger CLI will help you to debug in Interactively";


    /**
     * [handle description] this will execute and print sql results on table
     *
     * @return [type] [description]
     */
    public function handle() : void
    {
        $sh = new Shell();
        $sh->run();
    }


}
