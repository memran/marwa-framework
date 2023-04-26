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

class DBQuery extends AbstractCommand
{
    var $name="db:query {--query} {head*}";
    var $description="This command will execute database query";
    var $help ="db:query {--query} {head*}";
    var $argTitle = [
        'query' => "Please type your query",
        'head' => "Please provide table head"
        ];

    /**
     * [handle description] this will execute and print sql results on table
     *
     * @return [type] [description]
     */
    public function handle() : void
    {
         $sql = $this->option('query');

        if(empty($sql)) {
        	$this->info("Help : ".$this->help);
            $this->error("No sql query found");
        }

        try
        {
            $result = DB::rawQuery($sql);

            if(empty($result)) {
                $this->error("No Data Found");
            }
            if(!$result) {
                $this->error("Execution failed");
            }
            $this->info("Successfully executed");
            //it is array or not
            if(is_array($result)) {
                $head = $this->argument('head');
                $this->table($head, $result);
            }
            else
            {
                $this->info($result);
            }

        }
        catch (Exception $e)
        {
            $this->error($e->getMessage());
        }

    }


}
