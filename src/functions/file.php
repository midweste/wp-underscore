<?php

namespace _;

function files_get_contents( string $globPath, callable $callback = null ): string {
	$content = '';
	$files   = glob( $globPath );
	if ( count( $files ) == 0 ) {
		return $content;
	}

	$function = ( is_callable( $callback ) ) ? $callback : function ( string $filePath ) {
		return str_replace( [ PHP_EOL ], '', file_get_contents( $filePath ) );
	};

	foreach ( $files as $file ) {
		$content .= $function( $file );
	}
	return $content;
}

function file_callback_recursive( string $directory, callable $callback ): array {
	if ( ! is_dir( $directory ) ) {
		throw new \Exception( sprintf( 'Directory %s does not exist.', $directory ) );
	}
	$files    = [];
	$iterator = new \RecursiveDirectoryIterator( $directory );
	foreach ( new \RecursiveIteratorIterator( $iterator ) as $file ) {
		if ( ! is_dir( $file ) ) {
			if ( $callback( $file ) ) {
				$files[] = $file;
			}
		}
	}
	return $files;
}

function file_fnmatch_recursive( string $directory, string $match = '*' ): array {
	if ( ! is_dir( $directory ) ) {
		throw new \Exception( sprintf( 'Directory %s does not exist.', $directory ) );
	}
	$files    = [];
	$iterator = new \RecursiveDirectoryIterator( $directory );
	foreach ( new \RecursiveIteratorIterator( $iterator ) as $file ) {
		if ( ! is_dir( $file ) ) {
			if ( fnmatch( $match, $file ) ) {
				$files[] = $file;
			}
		}
	}
	return $files;
}

function file_fnmatch_recursive_walk( string $directory, string $match = '*', callable $function ): bool {
	$files = file_fnmatch_recursive( $directory, $match );
	foreach ( $files as $file ) {
		$function( $file );
	}
	return true;
}

function file_require_once_recursive( string $directory, string $match = '*.php' ): void {
	file_fnmatch_recursive_walk($directory, $match, function ( $file ) {
		require_once $file;
	});
}
