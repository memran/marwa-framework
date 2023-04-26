<?php
	/**
	 * @author    Mohammad Emran <memran.dhk@gmail.com>
	 * @copyright 2018
	 *
	 * @see https://www.github.com/memran
	 * @see http://www.memran.me
	 */
	
	namespace Marwa\Application\Commands;
	
	trait ConsoleCommandTrait {
		
		/**
		 * @var string
		 */
		var $fileDir = null;
		
		/**
		 * @param $tplFileName
		 * @param $cmdFileName
		 * @param $data
		 * @return bool
		 */
		public function generateFileFromTemplate( $tplFileName, $cmdFileName, $data )
		{
			$templateData = $this->readTpl($tplFileName);
			$parsedData = $this->parseTemplate($templateData, $data);
			
			return $this->writeTpl($cmdFileName, $parsedData);
		}
		
		/**
		 * @param $fileName
		 * @return false|string
		 */
		public function readTpl( $fileName )
		{
			$templatePath = $this->getTemplatePath() . $fileName . ".tpl";
			if ( !file_exists($templatePath) )
			{
				die("{$fileName} File does not exist in the path.");
			}
			if ( !is_readable($templatePath) )
			{
				die("{$fileName} File do not have read permission.");
			}
			$templateData = file_get_contents($templatePath);
			
			return $templateData;
		}
		
		/**
		 * @return string
		 */
		public function getTemplatePath()
		{
			$tplDirName = $this->getCommandPath();
			
			return $tplDirName . DS . "Templates" . DS;
		}
		
		/**
		 * @return string
		 */
		public function getCommandPath()
		{
			return dirname(__FILE__);
		}
		
		/**
		 * @param $templateData
		 * @param $data
		 * @return string|string[]
		 */
		public function parseTemplate( $templateData, $data )
		{
			if ( preg_match_all("/{{(.*?)}}/", $templateData, $m) )
			{
				foreach ( $m[1] as $i => $varname )
				{
					$templateData = str_replace($m[0][ $i ], sprintf('%s', $data[ $varname ]), $templateData);
				}
			}
			
			return $templateData;
		}
		
		/**
		 * @param $fileName
		 * @param $templateData
		 * @return bool
		 */
		public function writeTpl( $fileName, $templateData )
		{
			$fileDir = $this->getWritePath();
			
			if ( !is_dir($fileDir) )
			{
				mkdir($fileDir, 0777, true);
			}
			$filePathToWrite = $fileDir . $fileName . ".php";
			
			if ( file_exists($filePathToWrite) )
			{
				die("{$fileName} File name already exists on the path.");
			}
			
			if ( !is_writable($fileDir) )
			{
				die("{$fileName} File do not have write permission.");
			}
			
			
			if ( file_put_contents($filePathToWrite, $templateData) )
			{
				return true;
			}
			
			return false;
		}
		
		/**
		 * @return string|null
		 */
		public function getWritePath()
		{
			if ( is_null($this->fileDir) )
			{
				$this->setWriteDirPath();
				
				return $this->fileDir;
			}
			else
			{
				return $this->fileDir;
			}
		}
		
		/**
		 * @param null $path
		 */
		public function setWriteDirPath( $path = null )
		{
			if ( is_null($path) )
			{
				$this->fileDir = WEBROOT . DS . "app" . DS . "Commands" . DS;
			}
			else
			{
				$this->fileDir = $path;
			}
		}
	}
