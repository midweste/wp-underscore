<?php

namespace _;

/**
 * Return DateTimeZone in site timezone or America/Los_Angeles
 */
function datetimezone( string $default = 'America/Los_Angeles' ): \DateTimeZone {
	return new \DateTimeZone( get_option( 'timezone_string', $default ) );
}
