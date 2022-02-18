<?php

namespace _;

/**
 * Return the registered image styles along with their dimensions and crop settings
 *
 * @global array $_wp_additional_image_sizes
 *
 * @link http://core.trac.wordpress.org/ticket/18947 Reference ticket
 *
 * @return array $image_sizes The image sizes
 */
function image_styles_registered() {
	global $_wp_additional_image_sizes;

	$default_image_sizes = get_intermediate_image_sizes();

	$image_sizes = [];
	foreach ( $default_image_sizes as $size ) {
		$image_sizes[ $size ]['width']  = intval( get_option( "{$size}_size_w" ) );
		$image_sizes[ $size ]['height'] = intval( get_option( "{$size}_size_h" ) );
		$image_sizes[ $size ]['crop']   = get_option( "{$size}_crop" ) ? get_option( "{$size}_crop" ) : false;
	}

	if ( isset( $_wp_additional_image_sizes ) && count( $_wp_additional_image_sizes ) ) {
		$image_sizes = array_merge( $image_sizes, $_wp_additional_image_sizes );
	}
	// d($_wp_additional_image_sizes, $default_image_sizes, $image_sizes);
	return $image_sizes;
}

/**
 * Return a registered image style along with its dimensions and crop settings
 *
 * @param string $slug
 * @return array
 */
function image_style( string $slug ): array {
	$styles = image_styles_registered();
	return ( isset( $styles[ $slug ] ) ) ? $styles[ $slug ] : [];
}
