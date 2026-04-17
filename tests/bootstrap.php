<?php

declare(strict_types=1);

use Marwa\Framework\Supports\Runtime;

@mkdir(sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'marwa-sessions', 0777, true);
ini_set('session.save_path', sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'marwa-sessions');

// Override Runtime to simulate HTTP mode during tests
// This ensures HTTP-only services (Session, Security, View) are registered
Runtime::setConsoleOverride(false);
