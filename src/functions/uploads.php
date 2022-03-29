<?php

namespace _;

function uploads_basedir(): string
{
    return \wp_get_upload_dir()['basedir'];
}

/**
 * Returns a path relative to the uploads root without the starting /
 *
 * @param string $path
 * @return string
 */
function uploads_relative_path(string $path): string
{
    return ltrim(str_replace(uploads_basedir(), '', $path), '/');
}

/**
 * Returns a path relative to the uploads root without the starting /
 *
 * @param string $uri
 * @return string
 */
function uploads_relative_uri(string $uri): string
{
    return ltrim(str_replace(path_relative(uploads_basedir()), '', uri_relative($uri)), '/');
}

/**
 * Returns a path relative to the uploads root without the starting / from a path or uri
 *
 * @param string $path_or_uri
 * @return string
 */
function uploads_relative(string $path_or_uri): string
{
    return (is_uri($path_or_uri)) ? uploads_relative_uri($path_or_uri) : uploads_relative_path($path_or_uri);
}
