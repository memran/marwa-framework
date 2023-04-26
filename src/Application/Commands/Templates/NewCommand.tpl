<?php
/**
 * @author    Mohammad Emran <memran.dhk@gmail.com>
 * @copyright 2018
 *
 * @see      https://www.github.com/memran
 * @see      http://www.memran.me
 */

namespace App\Commands;
use Marwa\Application\Commands\AbstractCommand;

class {{COMMANDNAME}} extends AbstractCommand
{
	//this is command name
	var $name="my:command {username*}";

	//this is description for command
	var $description="This command will print current date and time";

	//this is help for command
	var $help = "This command allows you to show current date.";

	var $argTitle=
				[
					"username" => "What is your username",
					"queue" => "Execute Queue"
				];

	/**
	 * [__construct description]
	 */
	public function __construct()
    {
        parent::__construct();
    }

	public function handle() : void
	{

	}

}
