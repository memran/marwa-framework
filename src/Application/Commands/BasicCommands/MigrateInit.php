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
use Marwa\Application\Facades\DB;
use Marwa\Application\DBForge\Forge;
use Marwa\Application\Commands\MigrationCommandTrait;

class MigrateInit extends AbstractCommand
{
    use MigrationCommandTrait;
    var $name="migrate:init";
    var $description="This command will create migration table";
    var $help ="This command will create migration table";

    /**
     * [handle description]
     *
     * @return [type] [description]
     */
    public function handle() : void
    {
        $this->info("Migration initializing...");
        $res = $this->ask("It will drop migration table if exists and create again.Do you like to continue ? (y/N)");
        if(empty($res)) {
            $res="n";
        }

        if(strtolower($res)==="n") {
            $this->info("Migration initializing stopped");
            die;
        }
        //drop table if exists
        if(Forge::dropIfExists('migration')) {
            $this->info('Successfully dropped existing table');
        }
    
        //create new table
        $result = Forge::createTable($this->getMigrationTableSql());
        if($result) {
            $this->info("Successfully created migration table");
        }
        else{
            $this->info("Failed to initialize migration");
        }
        //synchronize with migration files if exists to the database
        $this->syncMigrationFiles();
        $this->info("Successfully migration initialized");
    }

    /**
     * @return string
     */
    protected function getMigrationTableSql()
    {
        return 'CREATE TABLE migration (id int(30), version varchar(100), applied_at varchar(100), description varchar(100))';
    }

    /**
     * [syncMigrationFiles description]
     *
     * @return [type] [description]
     */
    protected function syncMigrationFiles()
    {
        $readFiles = glob($this->getMigrationPath()."*.php");
        if(empty($readFiles)) {
            return false;
        }
        //$listFiles=[];
        foreach ($readFiles as $file) {
            $cmdName = basename($file, ".php"); //read base file name without extension
            list($id,$version) = explode('_', $cmdName);
            $this->migrationHistory($id, $cmdName, "Create ".$version);
        }

    }
}
