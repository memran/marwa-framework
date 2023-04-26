<?php
	declare( strict_types = 1 );

	namespace Marwa\Application\Jobs;
	
	abstract class AbstractListener {
		abstract public function handle( array $params = [] ) : void;
	}

