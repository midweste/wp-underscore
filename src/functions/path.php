<?php

namespace _;

function path_from_root(string $path): string
{
    require_once ABSPATH . 'wp-admin/includes/file.php';
    return str_replace(\get_home_path(), '/', $path);
}

function path_to_uri(string $path): string
{
    return home_url() . path_relative($path);
    // return WP_HOME . path_relative( $path );
}

function path_relative(string $path): string
{
	$relative = '/' . str_replace( ABSPATH, '', $path );
	return $relative;
}
