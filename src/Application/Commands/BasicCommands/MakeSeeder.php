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

class MakeSeeder extends AbstractCommand
{
    use ConsoleCommandTrait;
    use MigrationCommandTrait;
    //set command name
    var $name="make:seeder {name}";
    //set description of command
    var $description="It will generate seeder class";
    //set help of command
    var $help ="This command allows you to generate new seeder class.";

    var $argTitle=[
            'name'=> "Please enter Seeder name"
        ];

    /**
     * [handle description] generate new migration file
     *
     * @return [type] [description]
     */
    public function handle() : void
    {
        $seederName=$this->argument("name");
        if(empty($seederName)) {
            die("Seeder Class name not provided");
        }
        //replace the string
        $data = [
                'SEEDERNAME' => $seederName
                ];

        //set directory path for migraiton files
        $this->setWriteDirPath($this->getSeederPath());

        //writing to file
        $result = $this->generateFileFromTemplate('NewSeeder', $seederName, $data);
        //checking result
        if($result) {
            $this->info("Successfully generated seeder file ".$seederName);
        }
        else
        {
            $this->error("Failed to generate seeder file ".$seederName);
        }
    }

    /**
     * [getSeederPath description]
     *
     * @return [type] [description]
     */
    public function getSeederPath()
    {
        return WEBROOT.DS."database".DS."seeds".DS;
    }

}
