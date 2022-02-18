<?php

namespace _;

use Midweste\SimpleLogger\SessionLogger;
use Psr\Log\LogLevel;

function logger_session( string $name = '' ): SessionLogger {
	static $logger;
	if ( $logger instanceof SessionLogger ) {
		return $logger;
	}
	$logger = new SessionLogger( LogLevel::DEBUG, $name );
	return $logger;
}
