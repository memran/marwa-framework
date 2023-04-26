<?php
	
	
	namespace Marwa\Application\Views;
	
	use Marwa\Application\Views\Interfaces\FactoryInterface;
	use Marwa\Application\Views\Interfaces\ViewServiceInterface;
	
	class FactoryView implements FactoryInterface {
		
		/**
		 * @param string $type
		 * @param array $config
		 * @return ViewServiceInterface
		 * @throws \Exception
		 */
		public static function create( string $type, array $config ) : ViewServiceInterface
		{
			
			return new Twig($config);
		}
	}
