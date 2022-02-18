<?php

namespace _;

/**
 * Loads the matching filename to the plugin directory
 * require_once pluginname/pluginname.php
 *
 * @param string $directory
 * @return boolean
 */
function plugin_load( string $directory ): bool {
	if ( ! is_dir( $directory ) ) {
		throw new \Exception( sprintf( 'Directory %s does not exist.', $directory ) );
	}
	$directory  = rtrim( $directory, '/' );
	$pluginFile = basename( $directory ) . '.php';
	$pluginAbs  = $directory . '/' . $pluginFile;
	if ( ! is_file( $pluginAbs ) ) {
		return false;
	}

	require_once $pluginAbs;
	return true;
}

function plugin_load_all( string $directory ): bool {
	if ( ! is_dir( $directory ) ) {
		throw new \Exception( sprintf( 'Directory %s does not exist.', $directory ) );
	}
	$glob = rtrim( $directory, '/' ) . '/*';
	foreach ( glob( $glob, GLOB_ONLYDIR ) as $plugin ) {
		plugin_load( $plugin );
	}
	return true;
}
