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
use Marwa\Application\Commands\MigrationCommandTrait;
use Marwa\Application\Facades\DB;

class MigrateStatus extends AbstractCommand
{
    use MigrationCommandTrait;
    /**
     * [$name description]
     *
     * @var string
     */
    var $name="migrate:status";
    /**
     * [$description description]
     *
     * @var string
     */
    var $description="This command will print migration status";
    /**
     * [$help description]
     *
     * @var string
     */
    var $help ="This command will print migration status";

    public function handle() : void
    {
         //check migration table exists
        if(!$this->hasTable()) {
            $this->error("'migration' table does not exists on the database");
            die();
        }

        //execute migration qiery
        $result = $this->getAllMigration();
        //it is array or not
        if(is_array($result)) {
            $this->info("Creating Table....");
            $this->table(["id","version","applied_at","description"], $result);
        }
        else
        {
             $this->info("No result found");
            die;
        }

    }



}
