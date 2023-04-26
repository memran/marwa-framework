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

class GenerateCommand extends AbstractCommand
{
    use ConsoleCommandTrait;
    //this is command name
    var $name="make:command {name}";

    //this is description for command
    var $description="This command will generate command for you";

    //this is help for command
    var $help = "This command allows you to create new command.";

    var $argTitle=
                [
                    "name" => "Please input command name"
                ];

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
        $cmdName=$this->argument("name");
        if(is_null($cmdName)) {
            die("Command Name can not null");
        }
        $data = ['COMMANDNAME' => $cmdName];

        $result=$this->generateFileFromTemplate('NewCommand', $cmdName, $data);

        if($result) {
            $this->info("Successfully generated command file ".$cmdName);
        }
        else
        {
            $this->error("Failed to generate command file ".$cmdName);
        }

    }

}
