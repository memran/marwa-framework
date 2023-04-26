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
use Marwa\Application\Migrations\MigrationInterface;

class Migrate extends AbstractCommand
{
    var $name="migrate";
    var $description="This command will alias of migrate:up";
    var $help ="This command will up migration";

    public function handle() : void
    {
         $this->println("Processing Migration");
         $this->call("migrate:up");
    }
}
