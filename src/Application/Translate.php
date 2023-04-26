<?php
	
	namespace Marwa\Application;
	
	use Symfony\Component\Translation\Loader\ArrayLoader;
	use Symfony\Component\Translation\Translator;
	
	class Translate {
		
		/**
		 * @var Translator
		 */
		protected static $translator;
		
		/**
		 * Translate constructor.
		 */
		private function __construct()
		{
		
		}
		
		/**
		 * @return Translator
		 */
		public static function getInstance()
		{
			static::$translator = new Translator('en');
			static::$translator->addLoader('array', new ArrayLoader());
			static::$translator->setFallbackLocales(app()->getFallbackLocale());
			( new self() )->loadResource();
			
			return static::$translator;
		}
		
		/**
		 * @return bool
		 */
		protected function loadResource()
		{
			$langFiles = $this->getLangMessageFile();
			if ( !$langFiles ) return false;
			
			if ( is_array($langFiles) )
			{
				foreach ( $langFiles as $index => $file )
				{
					$message = include_once( $file['name'] );
					
					if ( is_array($message) )
					{
						static::$translator->addResource('array', $message, $file['lang']);
					}
				}
			}
			
			return true;
		}
		
		/**
		 *
		 */
		protected function getLangMessageFile()
		{
			$files = glob($this->getLangDirectory() . 'messages.*.php');
			
			if ( empty($files) )
			{
				return false;
			}
			$langFiles = [];
			foreach ( $files as $index => $file )
			{
				[$name, $lang, $extension] = explode('.', basename($file));
				$temArr = ['lang' => $lang, 'name' => $file];
				array_push($langFiles, $temArr);
			}
			
			return $langFiles;
		}
		
		/**
		 * @return string
		 */
		private function getLangDirectory() : string
		{
			return app('lang_path') . DIRECTORY_SEPARATOR;
		}
		
		/**
		 * @param string $name
		 * @param mixed $arguments
		 * @return mixed
		 */
		public function __call( $name, $arguments )
		{
			return call_user_func_array([static::$translator, $name], $arguments);
		}
		
	}
