<?php

namespace _;

function option_exists( string $option_name, $site_wide = false ) {
	global $wpdb;
	return $wpdb->query( $wpdb->prepare( 'SELECT 1 FROM ' . ( $site_wide ? $wpdb->base_prefix : $wpdb->prefix ) . "options WHERE option_name ='%s' LIMIT 1", $option_name ) );
}
