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
use Marwa\Application\Commands\MigrationCommandTrait;


class MigrateStatus extends AbstractCommand
{
    use MigrationCommandTrait;
    /**
     * [$name description]
     *
     * @var string
     */
    var $name = "migrate:status";
    /**
     * [$description description]
     *
     * @var string
     */
    var $description = "This command will print migration status in a table";
    /**
     * [$help description]
     *
     * @var string
     */
    var $help = "This command will print migration status";

    public function handle(): void
    {
        //check migration table exists
        if (!$this->hasTable()) {
            $this->error("'migration' table does not exists on the database");
        }

        //execute migration qiery
        $result = $this->getAllMigration();
        //it is array or not
        if (is_array($result)) {
            $this->info("Migration Status Table....");
            $this->table(["id", "version", "applied_at", "description"], $result);
        } else {
            $this->info("No migration found");
        }

    }



}