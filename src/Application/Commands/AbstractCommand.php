<?php
/**
 * @author    Mohammad Emran <memran.dhk@gmail.com>
 * @copyright 2018
 *
 * @see https://www.github.com/memran
 * @see http://www.memran.me
 */

namespace Marwa\Application\Commands;

use Marwa\Application\App;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

abstract class AbstractCommand extends Command
{

	/**
	 * [$name description]
	 *
	 * @var null
	 */
	var $name = null;
	/**
	 * [$cmdName description]
	 *
	 * @var null
	 */
	var $cmdName = null;
	/**
	 * [$description description]
	 *
	 * @var null
	 */
	var $description = null;
	/**
	 * [$help description]
	 *
	 * @var null
	 */
	var $help = null;
	/**
	 * [$input description]
	 *
	 * @var null
	 */
	var $input = null;
	/**
	 * [$output description]
	 *
	 * @var null
	 */
	var $output = null;
	/**
	 * [$app description]
	 *
	 * @var [type]
	 */
	var $app;

	/**
	 * [$argTitle description]
	 *
	 * @var array
	 */
	var $argTitle = [];

	/**
	 * [$argDefaultVal description]
	 *
	 * @var boolean
	 */
	var $argDefaultVal = false;


	/**
	 * [$progress description]
	 *
	 * @var integer
	 */
	var $progressBar = null;

	/**
	 * [$lockbar description]
	 *
	 * @var boolean
	 */
	var $lockbar = false;

	/**
	 * [$hidden description] to hide command
	 *
	 * @var boolean
	 */
	var $hidden = false;

	/**
	 * [__construct description]
	 */
	public function __construct()
	{
		parent::__construct();
	}

	/**
	 * [call description] function to call other command application
	 *
	 * @param  [type] $name [description]
	 * @return [type]       [description]
	 */
	public function call($name, $args = [])
	{
		$cmd = ['php', WEBROOT . DS . 'marwa', $name];
		if (!empty($args)) {
			array_merge($cmd, $args);
		}
		try {
			$process = new Process($cmd);
			$process->run();
			// executes after the command finishes
			if (!$process->isSuccessful()) {
				throw new ProcessFailedException($process);
			}
		} catch (\Throwable $e) {
			$this->error($e);
		}
		$output = $process->getOutput();
		if (isset($output)) {
			$this->println($output);
		}
	}

	/**
	 * [error description] function to print error color
	 *
	 * @param string $text [description]
	 * @return [type]       [description]
	 */
	public function error(?string $text)
	{
		$this->output->writeln("<error>{$text}</error>");
		die();
	}

	/**
	 * [print description] print a string to the console with new line \n
	 *
	 * @param string $cmd [description]
	 * @return [type]      [description]
	 */
	public function println(?string $cmd)
	{
		if (is_null($cmd)) {
			$this->output->writeln("null can not print");
		} else {
			$this->output->writeln($cmd);
		}

	}

	/**
	 * [exec description]
	 *
	 * @param  [type] $name [description]
	 * @param array $args [description]
	 * @return [type]       [description]
	 */
	public function exec($cmd, $timeout = 3600)
	{
		if (is_array($cmd)) {
			$args = $cmd;
		}
		if (is_string($cmd)) {
			$args = explode(" ", $cmd);
		}
		if (empty($args)) {
			$this->error("Command is empty");
			die;
		}

		try {
			$process = new Process($args);
			$process->setTimeout($timeout);
			$process->run();
			// executes after the command finishes
			if (!$process->isSuccessful()) {
				throw new ProcessFailedException($process);
			}
		} catch (\Throwable $e) {
			$this->error($e);
		}
		$output = $process->getOutput();
		if (isset($output)) {
			$this->println($output);
		}
	}

	/**
	 * [print description] print message without new line \n
	 *
	 * @param string $cmd [description]
	 * @return [type]      [description]
	 */
	public function print(?string $cmd)
	{
		$this->output->write($cmd);
	}

	/**
	 * [info description] function to print information text with color
	 *
	 * @param string $text [description]
	 * @return [type]       [description]
	 */
	public function info(?string $text)
	{
		$this->output->writeln("<info>{$text}</info>");
	}

	/**
	 * [warn description] function to print warning color
	 *
	 * @param string $text [description]
	 * @return [type]       [description]
	 */
	public function warn(?string $text)
	{
		$this->output->writeln("<comment>{$text}</comment>");
	}

	/**
	 * [link description] function to print clickable link
	 *
	 * @param  [type] $value [description]
	 * @param  [type] $href  [description]
	 * @return [type]        [description]
	 */
	public function link($value, $href)
	{
		$this->output->writeln("<href=$href>$value</>");
	}

	/**
	 * [argument description] function to retrieve argument value
	 *
	 * @param  [type] $cmdArgs [description]
	 * @return [type]          [description]
	 */
	public function argument($cmdArgs)
	{
		$input = $this->input->getArgument($cmdArgs);

		if (is_null($input) && $this->argDefaultVal) {
			return $this->argDefaultVal;
		} else {
			return $input;
		}
	}

	/**
	 * [options description]
	 *
	 * @param string $name [description]
	 * @return [type]       [description]
	 */
	public function option(string $name)
	{
		return $this->input->getOption($name);
	}

	/**
	 * [confirm description]
	 *
	 * @param  [type] $question [description]
	 * @return [type]           [description]
	 */
	public function confirm($question)
	{
		$helper = $this->getHelper('question');
		$getInput = new ConfirmationQuestion($question, false);
		if (!$helper->ask($this->input, $this->output, $getInput)) {
			return 0;
		} else {
			return 1;
		}
	}

	/**
	 * [choice description]
	 *
	 * @param  [type]  $question         [description]
	 * @param  [type]  $choices          [description]
	 * @param integer $default [description]
	 * @param boolean $allowMultiSelect [description]
	 * @return [type]                    [description]
	 */
	public function choice($question, $choices, $default = 0, $allowMultiSelect = false)
	{
		$helper = $this->getHelper('question');
		$cq = new ChoiceQuestion(
			$question,
			$choices,
			$default
		);
		if ($allowMultiSelect) {
			$cq->setMultiselect(true);
		}
		$color = $helper->ask($this->input, $this->output, $cq);

		return $color;
	}

	/**
	 * [secret description] function to get hidden input
	 *
	 * @param  [type] $question [description]
	 * @return [type]           [description]
	 */
	public function secret($question)
	{
		return $this->ask($question, true);
	}

	/**
	 * @param $question
	 * @param bool $hidden
	 * @return int
	 */
	public function ask($question, $hidden = false)
	{
		$helper = $this->getHelper('question');
		$ques = new Question($question, false);
		if ($hidden) {
			$ques->setHidden(true);
			$ques->setHiddenFallback(false);
		}

		return $helper->ask($this->input, $this->output, $ques);

	}

	/**
	 * Similiar function  to ask
	 * i.e. alise of ask()
	 * */
	public function prompt($question, $hidden = false)
	{
		return $this->ask($question, $hidden = false);
	}
	/**
	 * @param mixed $msg
	 */
	public function dump($msg)
	{
		var_dump($msg);
		die();
	}

	/**
	 * @param array $header
	 * @param array $tableData
	 * @param null $title
	 */
	public function table(array $header, array $tableData, $title = null)
	{
		$table = new Table($this->output);
		if (!is_null($title)) {
			$table->setHeaderTitle($title);
		}
		$table
			->setHeaders($header)
			->setRows($tableData);

		$table->render();
	}

	/**
	 * @param null $units
	 * @return ProgressBar
	 */
	public function getProgressBar($units = null)
	{
		// creates a new progress bar (50 units)
		$progressBar = new ProgressBar($this->output);
		if (!is_null($units)) {
			$progressBar->setMaxSteps($units);
		}

		return $progressBar;
	}

	/**
	 * @return void
	 */
	protected function configure(): void
	{
		$this->parseCommandString();
	}

	//**

	protected function parseCommandString()
	{
		//remove white space
		$cmd = trim($this->name);
		$cmd_arr = explode(" ", $cmd);
		$this->cmdName = $cmd_arr[0];

		$this->setCommand();
		array_shift($cmd_arr);

		foreach ($cmd_arr as $key => $value) {
			//if enable option
			if (strstr($value, "--")) {
				$this->setOptions($value);
			} else //enable argument
			{
				$this->setArgument($value);
			}
		}
	}

	/**
	 *
	 */
	protected function setCommand()
	{
		if (!is_null($this->cmdName)) {
			//function to set name, description and help
			$this->setName($this->cmdName)
				->setDescription($this->description)
				->setHelp($this->help);

			//hide the command from list
			if ($this->hidden) {
				$this->setHidden(true);
			}
		} else {
			$this->error("Command name is not found");
		}
	}

	/**
	 * @param string $cmdStr
	 */
	protected function setOptions($cmdStr)
	{
		$cmd = $this->rmCurlyBracs($cmdStr);
		$cmd = ltrim($cmd, "--");
		$optionName = null;
		$optionShortCut = null;
		$optionValue = false;
		$optionDemand = InputOption::VALUE_REQUIRED;

		//enable option shortcut
		if (strstr($cmd, '|')) {
			$cmdShortCutArray = explode('|', $cmd);
			$optionShortCut = $cmdShortCutArray[0];
			$cmd = $cmdShortCutArray[1];
		}

		//enable option assigned value
		if (strstr($cmd, '=')) {
			$values = explode("=", $cmd);
			$optionName = $values[0];
			$optionValue = $values[1];
		}
		if (strstr($cmd, '*')) //enable option array
		{
			$values = rtrim($cmd, "*");
			$optionName = $values;
			$optionDemand = InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY;
			$optionValue = [];
		} else {
			$optionName = $cmd;
		}

		if (empty($this->argTitle)) {
			die("Argument Title is empty for command " . $this->cmdName);
		}

		//add option
		$this->addOption(
			$optionName,
			$optionShortCut,
			$optionDemand,
			$this->argTitle[$optionName],
			$optionValue
		);

	}

	/**
	 * @param $str
	 * @return false|string
	 */
	protected function rmCurlyBracs($str)
	{
		return substr($str, 1, -1);
	}

	/**
	 * @param $option
	 */
	protected function setArgument($option)
	{
		$args = $this->rmCurlyBracs($option);
		$argName = null;
		$argOption = InputArgument::REQUIRED;

		if (strstr($args, '?')) {
			$argOption = InputArgument::OPTIONAL;
			$values = rtrim($args, '?');
			$argName = $values;
		} else {
			if (strstr($args, '*')) {
				$argOption = InputArgument::IS_ARRAY;
				$values = rtrim($args, '*');
				$argName = $values;
			} else {
				if (strstr($args, '=')) {
					//echo "it is optional argument with default value";
					$values = explode("=", $args);
					$argName = $values[0];
					$this->argDefaultVal = $values[1];
					$argOption = InputArgument::OPTIONAL;
				} else {
					$argOption = InputArgument::REQUIRED;
					$argName = $args;
				}
			}
		}
		//check argument title configured or not
		if (empty($this->argTitle)) {
			die("Argument title not configured");
		}
		//add arguments
		$this->addArgument($argName, $argOption, $this->argTitle[$argName]);

	}

	/**
	 * @param InputInterface $input
	 * @param OutputInterface $output
	 */
	protected function execute(InputInterface $input, OutputInterface $output)
	{
		$this->input = $input;
		$this->output = $output;

		//loading main application
		if (!isset($this->app)) {
			$this->app = new App(WEBROOT, true);
		}

		//execute handle
		$this->handle();
		return 0; // Return 0 for success
	}

	/**
	 *
	 */
	abstract public function handle(): void;

}

?>