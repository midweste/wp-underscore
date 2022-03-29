<?php

namespace _;

function uploads_basedir(): string
{
    return \wp_get_upload_dir()['basedir'];
}

function uploads_baseuri(): string
{
    return path_to_uri(uploads_basedir());
}

/**
 * Returns a path relative to the uploads root without the starting /
 *
 * @param string $path
 * @return string
 */
function uploads_relative_path(string $path): string
{
    $basedir = uploads_basedir();
    return ltrim(str_replace($basedir, '', $path), '/');
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
