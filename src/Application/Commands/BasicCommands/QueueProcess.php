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
use Marwa\Application\Jobs\JobWorker;
use Marwa\Application\Facades\DB;


class QueueProcess extends AbstractCommand
{
    /**
     * [$name description]
     *
     * @var string
     */
    var $name="queue:run";
    /**
     * [$description description]
     *
     * @var string
     */
    var $description="It will process queue jobs";
    /**
     * [$help description]
     *
     * @var string
     */
    var $help ="This command allows you to process queue jobs.";

    /**
     * [handle description] generate new migration file
     *
     * @return [type] [description]
     */
    public function handle() : void
    {
        (new JobWorker())->run();
    }


}

