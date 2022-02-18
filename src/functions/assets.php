<?php

namespace _;

/**
 * Helper function for registering and enqueueing inline scripts and styles.
 *
 * @param string $handle
 * @param string $content
 * @param boolean $script
 * @return void
 */
function enqueue_inline( string $handle, string $content, bool $script = false ): void {
	if ( $script ) {
		wp_register_script( $handle, false );
		wp_enqueue_script( $handle );
		wp_add_inline_script( $handle, $content );
	} else {
		wp_register_style( $handle, false );
		wp_enqueue_style( $handle );
		wp_add_inline_style( $handle, $content );
	}
}

/**
 * Helper function for registering and enqueueing local and remote scripts and styles.
 *
 * @param string $handle
 * @param string $content
 * @param array $depends
 * @return void
 */
function enqueue( string $handle, string $file_path, array $depends = [], $ver = false, $footer_or_media = true ): void {
	// convert to uri if path given
	$parsed = parse_url( $file_path );
	if ( ! isset( $parsed['host'] ) ) {
		if ( ! file_exists( $file_path ) ) {
			throw new \Exception( sprintf( 'Could not enqueue file %s.  File does not exist.', $file_path ) );
		}
		$file_path = path_to_uri( $file_path );
	}

	$pathinfo  = pathinfo( $file_path );
	$is_script = ( isset( $pathinfo['extension'] ) && $pathinfo['extension'] == 'js' ) ? true : false;

	if ( $is_script ) {
		wp_register_script( $handle, $file_path, $depends, $ver, $footer_or_media );
		wp_enqueue_script( $handle );
	} else {
		// todo $footer needs to be media
		$media = ( is_string( $footer_or_media ) ) ? $footer_or_media : 'all';
		wp_register_style( $handle, $file_path, $depends, $ver, $media );
		wp_enqueue_style( $handle );
	}
}
