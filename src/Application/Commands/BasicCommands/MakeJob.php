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

class MakeJob extends AbstractCommand
{
    use ConsoleCommandTrait;
    use MigrationCommandTrait;
    //set command name
    var $name="make:job {name}";
    //set description of command
    var $description="It will generate job class";
    //set help of command
    var $help ="This command allows you to generate new job class.";

    /**
     * [$argTitle description]
     *
     * @var [type]
     */
    var $argTitle=[
            'name'=> "Provide Job Class Name"
        ];

    /**
     * [handle description] generate new migration file
     *
     * @return [type] [description]
     */
    public function handle() : void
    {
        $jobName=$this->argument("name");
        if(empty($jobName)) {
            die("Job Class name not provided");
        }
        //replace the string
        $data = [
                'CLASSNAME' => $jobName
                ];

        //set directory path for migraiton files
        $this->setWriteDirPath($this->getJobPath());

        //writing to file
        $result = $this->generateFileFromTemplate('NewJob', $jobName, $data);
        //checking result
        if($result) {
            $this->info("Successfully generated job file ".$jobName);
        }
        else
        {
            $this->error("Failed to generate job file ".$jobName);
        }
    }

    /**
     * [getSeederPath description]
     *
     * @return [type] [description]
     */
    public function getJobPath()
    {
        return WEBROOT.DS."app".DS."Jobs".DS;
    }

}
