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
use Marwa\Application\Commands\ConsoleCommandTrait;
use Marwa\Application\Commands\MigrationCommandTrait;
use Marwa\Application\Facades\DB;


class ScheduleTable extends AbstractCommand
{
    use ConsoleCommandTrait;
    use MigrationCommandTrait;
    //set command name
    var $name="schedule:table";
    //set description of command
    var $description="It will generate schedule migration";
    //set help of command
    var $help ="This command allows you to generate schedule migration.";

    /**
     * [handle description] generate new migration file
     *
     * @return [type] [description]
     */
    public function handle() : void
    {
        $migrationName="ScheduleMigration";
        $tableName = 'schedule';
        $desc = "create schedule table";

        //generate migration time
        $id = time();
        $migrationFile = $id.'_'.$migrationName;
        //replace the string
        $data = [
                'MIGRATIONNAME' => $migrationName,
                'MIGRATIONTABLE' => $tableName
                ];

        //set directory path for migraiton files
        $this->setWriteDirPath($this->getMigrationPath());

        //writing to file
        $result = $this->generateFileFromTemplate('ScheduleMigration', $migrationFile, $data);
        //checking result
        if($result) {
            $this->migrationHistory($id, $migrationFile, $desc);
            $this->info("Successfully generated migration file ".$migrationFile);
        }
        else
        {
            $this->error("Failed to generate migration file ".$migrationFile);
        }
    }

}
