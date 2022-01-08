<?php

namespace _;

use MatthiasMullie\Minify;

function files_aggregate_conditionally(string $globPath, int $expires = 0, callable $callback = null)
{
    $conditionals = conditional_types();
    $conditionalHtml = '';

    // include root level global files
    $conditionalHtml .= cache_glob_files($globPath, $globPath, $expires, $callback);

    $base = dirname($globPath);
    foreach ($conditionals as $conditional) {
        $conditionalPath = $base . '/' . $conditional;
        if (is_dir($conditionalPath)) {
            $conditionalPath = addslashes($conditionalPath);
            $absPath = str_replace($base, $conditionalPath, $globPath);
            $conditionalHtml .= cache_glob_files($absPath, $absPath, $expires, $callback);
        }
    }
    return $conditionalHtml;
}

function js_aggregate(string $globPath, bool $minify = true): string
{
    $conditionals = conditional_types();
    $cacheName = cache_name_create(__FUNCTION__, $conditionals);
    if (cache_has($cacheName)) {
        return cache_get($cacheName);
    }
    $content = cache_glob_files($globPath, $globPath, 0, function (string $globPath) {
        $script = sprintf(' /* %s */ ', path_relative($globPath)) . PHP_EOL;
        $script .= 'try{';
        $script .= file_get_contents($globPath) . PHP_EOL;
        $script .= '}catch(e){console.error("An error has occurred: "+e.stack);}' . PHP_EOL;
        return $script;
    });
    if ($minify) {
        $minifier = new Minify\JS();
        $content = $minifier->add($content)->minify();
    }
    $html = sprintf('<script type="text/javascript">%s</script>', $content);
    return cache_set($cacheName, $html, 0);
}

function js_aggregate_conditionally(string $directory, bool $minify = true): string
{
    $conditionals = conditional_types();
    $cacheName = cache_name_create(__FUNCTION__, $conditionals);
    if (cache_has($cacheName)) {
        return cache_get($cacheName);
    }
    $content = files_aggregate_conditionally($directory . '/*.js', 0, function (string $directory) {
        $script = sprintf(' /* %s */ ', path_relative($directory)) . PHP_EOL;
        $script .= 'try{';
        $script .= file_get_contents($directory) . PHP_EOL;
        $script .= '}catch(e){console.error("An error has occurred: "+e.stack);}' . PHP_EOL;
        return $script;
    });
    if ($minify) {
        $minifier = new Minify\JS();
        $content = $minifier->add($content)->minify();
    }
    $html = sprintf('<script type="text/javascript">%s</script>', $content);
    return cache_set($cacheName, $html, 0);
}

function css_aggregate(string $globPath, bool $minify = true): string
{
    $conditionals = conditional_types();
    $cacheName = cache_name_create(__FUNCTION__, $conditionals);
    if (cache_has($cacheName)) {
        return cache_get($cacheName);
    }
    $content = cache_glob_files($globPath, $globPath, 0, function (string $globPath) {
        $styles = sprintf(' /* %s */ ', path_relative($globPath)) . PHP_EOL;
        $styles .= str_replace([PHP_EOL], '', file_get_contents($globPath));
        return $styles;
    });
    if ($minify) {
        $minifier = new Minify\CSS();
        $content = $minifier->add($content)->minify();
    }
    $html = sprintf("<style>%s</style>", $content);
    return cache_set($cacheName, $html, 0);
}

function css_aggregate_conditionally(string $directory, bool $minify = true): string
{
    $conditionals = conditional_types();
    $cacheName = cache_name_create(__FUNCTION__, $conditionals);
    if (cache_has($cacheName)) {
        return cache_get($cacheName);
    }
    $content = files_aggregate_conditionally($directory . '/*.css', 0, function (string $directory) {
        $styles = sprintf(' /* %s */ ', path_relative($directory)) . PHP_EOL;
        $styles .= str_replace([PHP_EOL], '', file_get_contents($directory));
        return $styles;
    });
    if ($minify) {
        $minifier = new Minify\CSS();
        $content = $minifier->add($content)->minify();
    }
    $html = sprintf("<style>%s</style>", $content);
    return cache_set($cacheName, $html, 0);
}
