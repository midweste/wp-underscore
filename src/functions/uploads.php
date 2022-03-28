<?php

namespace _;

function uploads_basedir(): string
{
    return \wp_get_upload_dir()['basedir'];
}

function uploads_relative_path(string $path): string
{
    return str_replace(uploads_basedir(), '', $path);
}

function uploads_relative_uri(string $uri): string
{
    return str_replace(path_relative(uploads_basedir()), '', uri_relative($uri));
}
