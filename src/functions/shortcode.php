<?php

namespace _;

function do_shortcode( string $content, bool $ignore_html = false ) {
	// https://wordpress.stackexchange.com/questions/22524/do-shortcode-not-working-for-embed
	if ( \has_shortcode( $content, 'embed' ) ) {
		global $wp_embed;
		$content = $wp_embed->run_shortcode( $content );
	}
	return \do_shortcode( $content, $ignore_html );
}
