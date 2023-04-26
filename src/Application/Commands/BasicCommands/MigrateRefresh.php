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

class MigrateRefresh extends AbstractCommand
{
    var $name="migrate:refresh";
    var $description="This command will refresh migration";
    var $help ="This command will refresh migration";

    public function handle() : void
    {
        $this->call("migrate:down");
        $this->call("migrate:up");
    }





}
