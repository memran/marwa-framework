<?php
/**
 * @author    Mohammad Emran <memran.dhk@gmail.com>
 * @copyright 2018
 *
 * @see https://www.github.com/memran
 * @see http://www.memran.me
 */

namespace Marwa\Application\Commands\BasicCommands;
use Marwa\Application\Commands\AbstractCommand;
use Marwa\Application\Jobs\ScheduleWorker;

class ScheduleRun extends AbstractCommand
{
    //this is command name
    var $name="schedule:run";

    //this is description for command
    var $description="This command will run schedule server";

    //this is help for command
    var $help = "This command allows you to run schedule server.";

    /**
     * [__construct description]
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * [handle description]
     *
     * @return [type] [description]
     */
    public function handle() : void
    {
        (new ScheduleWorker())->run();
    }

}
