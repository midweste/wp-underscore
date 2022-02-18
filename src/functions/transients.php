<?php

namespace _;

function transients_flush(): void {
	global $wpdb;
	$sql    = "
            DELETE
            FROM wp_options
            WHERE option_name like '_transient_%'
        ";
	$result = $wpdb->query( $sql );
}

function transients_delete_expired(): void {
	global $wpdb, $_wp_using_ext_object_cache;

	$time    = isset( $_SERVER['REQUEST_TIME'] ) ? (int) $_SERVER['REQUEST_TIME'] : time();
	$expired = $wpdb->get_col( "SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout%' AND option_value < {$time};" );

	foreach ( $expired as $transient ) {
		$key     = str_replace( '_transient_timeout_', '', $transient );
		$deleted = delete_transient( $key );
	}

	if ( $_wp_using_ext_object_cache ) {
		wp_cache_flush();
		return;
	}
}
