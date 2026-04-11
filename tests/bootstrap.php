<?php

declare(strict_types=1);

use Marwa\Framework\Supports\Runtime;

// Override Runtime to simulate HTTP mode during tests
// This ensures HTTP-only services (Session, Security, View) are registered
Runtime::setConsoleOverride(false);